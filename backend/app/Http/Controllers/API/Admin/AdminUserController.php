<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Services\Admin\UserService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminUserController extends Controller
{
    use ApiResponseTrait;

    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
        $this->userService = $userService;
    }

    /**
     * Get paginated list of all users
     * GET /api/admin/users
     */
    public function index(Request $request)
    {
        try {
            $users = $this->userService->getUsers($request);
            return $this->paginationResponse($users, 'Users retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get single user details
     * GET /api/admin/users/{id}
     */
    public function show($id)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            return $this->successResponse($user, 'User retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Create a new user
     * POST /api/admin/users
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
                'role' => 'required|in:customer,vendor,admin',
                'email_verified' => 'sometimes|boolean',
            ]);

            $user = $this->userService->createUser($validated);
            return $this->createResponse($user, 'User created successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update user
     * PUT /api/admin/users/{id}
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $id,
                'role' => 'sometimes|in:customer,vendor,admin',
                'status' => 'sometimes|in:active,inactive,suspended,pending',
                'password' => 'sometimes|nullable|string|min:8|confirmed',
                'email_verified' => 'sometimes|boolean',
            ]);

            $updatedUser = $this->userService->updateUser($user, $validated);

            return $this->updatedResponse($updatedUser, 'User updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Delete user (soft delete)
     * DELETE /api/admin/users/{id}
     */
    public function destroy($id)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            // Prevent deleting self
            if ($user->id === Auth::id()) {
                return $this->errorResponse('You cannot delete your own account', 400);
            }

            // Check if user has orders
            $orderCount = Order::where('user_id', $user->id)->count();
            if ($orderCount > 0) {
                return $this->errorResponse(
                    'Cannot delete user with existing orders. Please anonymize or reassign orders first.',
                    400
                );
            }

            $this->userService->deleteUser($user);

            return $this->deletedResponse('User deleted successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Restore soft-deleted user
     * POST /api/admin/users/{id}/restore
     */
    public function restore($id)
    {
        try {
            $user = User::withTrashed()->find($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            if (!$user->trashed()) {
                return $this->errorResponse('User is not deleted', 400);
            }

            $this->userService->restoreUser($user);

            return $this->successResponse($user, 'User restored successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get user orders
     * GET /api/admin/users/{id}/orders
     */
    public function orders($id, Request $request)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $orders = $this->userService->getUserOrders($user, $request);
            return $this->paginationResponse($orders, 'User orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get user activities
     * GET /api/admin/users/{id}/activities
     */
    public function activities($id, Request $request)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $activities = $this->userService->getUserActivities($user, $request);
             if ($activities instanceof \Illuminate\Pagination\LengthAwarePaginator) {
            return $this->paginationResponse($activities, 'User activities retrieved successfully');
        }
        return $this->successResponse($activities, 'User activities retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get user statistics
     * GET /api/admin/users/{id}/stats
     */
    public function stats($id)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $stats = $this->userService->getUserStats($user);
            return $this->successResponse($stats, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Export user activities
     * GET /api/admin/users/{id}/activities/export
     */
    public function exportActivities($id, Request $request)
    {
        try {
            $user = $this->userService->getUserById($id);

            if (!$user) {
                return $this->notFoundResponse('User not found');
            }

            $activities = $this->userService->getUserActivities($user, $request, true);

            $headers = ['ID', 'Action', 'Description', 'Amount', 'Status', 'Created At'];
            $rows = $activities->map(function($activity) {
                return [
                    $activity->id,
                    $activity->action,
                    $activity->description,
                    $activity->amount ?? 0,
                    $activity->status ?? 'N/A',
                    $activity->created_at,
                ];
            });

            $filename = "user_{$id}_activities_" . now()->format('Y-m-d') . '.csv';

            return response()->streamDownload(function() use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Bulk delete users
     * POST /api/admin/users/bulk-delete
     */
    public function bulkDelete(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'required|exists:users,id',
            ]);

            $deletedCount = $this->userService->bulkDeleteUsers($validated['user_ids']);

            return $this->successResponse(
                ['deleted_count' => $deletedCount],
                "{$deletedCount} users deleted successfully"
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Bulk update user status
     * POST /api/admin/users/bulk-status
     */
    public function bulkStatus(Request $request)
    {
        try {
            $validated = $request->validate([
                'user_ids' => 'required|array|min:1',
                'user_ids.*' => 'required|exists:users,id',
                'status' => 'required|in:active,inactive,suspended',
            ]);

            $updatedCount = $this->userService->bulkUpdateStatus($validated['user_ids'], $validated['status']);

            return $this->successResponse(
                ['updated_count' => $updatedCount],
                "{$updatedCount} users updated successfully"
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }
}
