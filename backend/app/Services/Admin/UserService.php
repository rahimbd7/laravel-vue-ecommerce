<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService
{
    /**
     * Get paginated users with filters
     */
    public function getUsers(Request $request)
    {
        $query = User::withTrashed()->with(['vendor', 'profile']);

        // Search
        if ($request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('email', 'LIKE', "%{$search}%")
                  ->orWhere('uuid', 'LIKE', "%{$search}%");
            });
        }

        // Filter by role
        if ($request->role) {
            $query->where('role', $request->role);
        }

        // Filter by status (active = not deleted, inactive = deleted)
        if ($request->status) {
            if ($request->status === 'active') {
                $query->whereNull('deleted_at');
            } elseif ($request->status === 'inactive') {
                $query->whereNotNull('deleted_at');
            }
        }

        // Filter by email verification
        if ($request->email_verified !== null && $request->email_verified !== '') {
            if ($request->email_verified) {
                $query->whereNotNull('email_verified_at');
            } else {
                $query->whereNull('email_verified_at');
            }
        }

        // Sort
        $sortBy = $request->sort_by ?? 'created_at';
        $sortOrder = $request->sort_order ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Paginate
        $perPage = $request->per_page ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Get single user by ID
     */
    public function getUserById($id)
    {
        return User::withTrashed()
            ->with(['vendor', 'profile', 'orders'])
            ->find($id);
    }

    /**
     * Create a new user
     */
    public function createUser(array $data)
    {
        return DB::transaction(function () use ($data) {
             $emailVerifiedAt = isset($data['email_verified']) && $data['email_verified'] === true
            ? now()
            : null;
            $user = User::create([
                'uuid' => Str::uuid(),
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role' => $data['role'],
                'email_verified_at' => $emailVerifiedAt,
            ]);

            return $user;
        });
    }

    /**
     * Update user
     */
   public function updateUser(User $user, array $data)
{
    return DB::transaction(function () use ($user, $data) {
        // Handle password
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        // Handle email verification
        if (isset($data['email_verified'])) {
            if ($data['email_verified'] === true && !$user->email_verified_at) {
                $data['email_verified_at'] = now();
            } elseif ($data['email_verified'] === false && $user->email_verified_at) {
                $data['email_verified_at'] = null;
            }
            unset($data['email_verified']);
        }

        // Handle status (soft delete/restore)
        if (isset($data['status'])) {
            if ($data['status'] === 'inactive' && !$user->trashed()) {
                $user->delete();
            } elseif ($data['status'] === 'active' && $user->trashed()) {
                $user->restore();
            } elseif ($data['status'] === 'suspended' && !$user->trashed()) {
                $user->update(['status' => 'suspended']);
            }
            unset($data['status']);
        }

        $user->update($data);

        return $user->fresh();
    });
}

    /**
     * Delete user (soft delete)
     */
    public function deleteUser(User $user)
    {
        return DB::transaction(function () use ($user) {
            $user->delete();
            return true;
        });
    }

    /**
     * Restore soft-deleted user
     */
    public function restoreUser(User $user)
    {
        return DB::transaction(function () use ($user) {
            $user->restore();
            return $user;
        });
    }

    /**
     * Get user orders
     */
    public function getUserOrders(User $user, Request $request, $all = false)
    {
        $query = $user->orders()
            ->with(['vendor', 'items.product']);

        if ($request->status) {
            $query->where('status', $request->status);
        }

        if ($request->search) {
            $search = $request->search;
            $query->where('order_number', 'LIKE', "%{$search}%");
        }

        $query->orderBy('created_at', 'desc');

        if ($all) {
            return $query->get();
        }

        $perPage = $request->per_page ?? 10;
        return $query->paginate($perPage);
    }

    /**
     * Get user activities
     */
    public function getUserActivities(User $user, Request $request, $all = false)
    {
        $query = TransactionLog::where('user_id', $user->id);

    if ($request->type) {
        $query->where('action', 'LIKE', "%{$request->type}%");
    }

    $query->orderBy('created_at', 'desc');

    if ($all) {
        return $query->get();
    }

    $perPage = $request->per_page ?? 15;
    return $query->paginate($perPage);
    }

    /**
     * Get user statistics
     */
   public function getUserStats(User $user)
{
    return [
        'total_orders' => $user->orders()->count(),
        'total_spent' => $user->orders()->where('payment_status', 'paid')->sum('grand_total') ?? 0,
        'total_reviews' => $user->reviews()->count() ?? 0,
        // Or if you used 'productReviews':
        // 'total_reviews' => $user->productReviews()->count() ?? 0,
        'wishlist_count' => $user->wishlist()->count() ?? 0,
        'last_order_date' => $user->orders()->latest()->first()?->created_at,
        'account_age_days' => $user->created_at->diffInDays(now()),
        'last_login' => $user->last_login_at,
        'is_verified' => !is_null($user->email_verified_at),
    ];
}

    /**
     * Bulk delete users
     */
    public function bulkDeleteUsers(array $userIds)
    {
        return DB::transaction(function () use ($userIds) {
            // Prevent deleting self
            $userId = Auth::id();
            $userIds = array_filter($userIds, function($id) use ($userId) {
                return $userId !== $id;
            });

            // Check if any users have orders
            $usersWithOrders = User::whereIn('id', $userIds)
                ->whereHas('orders')
                ->count();

            if ($usersWithOrders > 0) {
                throw new \Exception('Some users have orders and cannot be deleted');
            }

            $count = User::whereIn('id', $userIds)->delete();
            return $count;
        });
    }

    /**
     * Bulk update user status
     */
    public function bulkUpdateStatus(array $userIds, string $status)
    {
        return DB::transaction(function () use ($userIds, $status) {
            $count = 0;

            foreach ($userIds as $id) {
                $user = User::find($id);
                if ($user) {
                    if ($status === 'inactive' && !$user->trashed()) {
                        $user->delete();
                        $count++;
                    } elseif ($status === 'active' && $user->trashed()) {
                        $user->restore();
                        $count++;
                    } elseif ($status === 'suspended' && !$user->trashed()) {
                        $user->update(['status' => 'suspended']);
                        $count++;
                    }
                }
            }

            return $count;
        });
    }
}
