<?php

namespace App\Services;

use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class VendorProfileService
{
    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    public function getProfile(User $user): array
    {
        $vendor = $user->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor profile not found');
        }

        $userProfile = $this->profileService->getProfile($user);

        return [
            'user' => [
                'id' => $user->id,
                'uuid' => $user->uuid,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'profile' => $userProfile->profile,
                'created_at' => $user->created_at,
            ],
            'vendor' => [
                'id' => $vendor->id,
                'business_name' => $vendor->business_name,
                'business_email' => $vendor->business_email,
                'business_phone' => $vendor->business_phone,
                'tax_number' => $vendor->tax_number,
                'website' => $vendor->website,
                'description' => $vendor->description,
                'store_logo' => $vendor->store_logo ? Storage::url($vendor->store_logo) : null,
                'commission_rate' => $vendor->commission_rate,
                'is_verified' => $vendor->is_verified,
                'verified_at' => $vendor->verified_at,
                'status' => $vendor->status,
                'created_at' => $vendor->created_at,
                'updated_at' => $vendor->updated_at,
            ],
            'stats' => [
                'total_products' => $vendor->products()->count(),
                // ✅ REMOVED: orders() relationship - use Order::where('vendor_id', $vendor->id) instead
                'total_orders' => \App\Models\Order::where('vendor_id', $vendor->id)->count(),
                'total_revenue' => \App\Models\Order::where('vendor_id', $vendor->id)
                    ->where('payment_status', 'paid')
                    ->sum('grand_total'),
            ]
        ];
    }

    public function updateProfile(User $user, array $data): array
    {
        return DB::transaction(function () use ($user, $data) {
            $vendor = $user->vendor;

            if (!$vendor) {
                throw new \Exception('Vendor profile not found');
            }

            if (isset($data['name']) || isset($data['email']) || isset($data['phone'])) {
                $this->profileService->updateProfile($user, $data);
            }

            $vendorData = array_intersect_key($data, array_flip([
                'business_name',
                'business_email',
                'business_phone',
                'tax_number',
                'website',
                'description',
            ]));

            $vendor->update($vendorData);

            return $this->getProfile($user);
        });
    }

    public function updateLogo(User $user, $logoFile): array
    {
        $vendor = $user->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor profile not found');
        }

        if ($vendor->store_logo) {
            Storage::disk('public')->delete($vendor->store_logo);
        }

        $path = $logoFile->store('vendors/logos', 'public');
        $vendor->store_logo = $path;
        $vendor->save();

        return [
            'store_logo' => Storage::url($path),
            'store_logo_path' => $path,
        ];
    }

    public function updateShippingSettings(User $user, array $data): array
    {
        $vendor = $user->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor profile not found');
        }

        $existingSettings = $vendor->shipping_settings ?? [];

        $shippingData = array_merge($existingSettings, array_intersect_key($data, array_flip([
            'free_shipping_threshold',
            'default_shipping_rate',
            'shipping_zones',
            'delivery_min_days',
            'delivery_max_days',
        ])));

        $vendor->shipping_settings = $shippingData;
        $vendor->save();

        return $shippingData;
    }

    public function getShippingSettings(User $user): ?array
    {
        $vendor = $user->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor profile not found');
        }

        return $vendor->shipping_settings ?? [
            'free_shipping_threshold' => 0,
            'default_shipping_rate' => 0,
            'shipping_zones' => [],
            'delivery_min_days' => 1,
            'delivery_max_days' => 5,
        ];
    }

    public function getVendorStats(User $user): array
    {
        $vendor = $user->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor profile not found');
        }

        return [
            'total_products' => $vendor->products()->count(),
            // ✅ REMOVED: orders() relationship - use Order::where('vendor_id', $vendor->id)
            'total_orders' => \App\Models\Order::where('vendor_id', $vendor->id)->count(),
            'pending_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('status', 'pending')
                ->count(),
            'processing_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('status', 'processing')
                ->count(),
            'confirmed_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('status', 'confirmed')
                ->count(),
            'shipped_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('status', 'shipped')
                ->count(),
            'delivered_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('status', 'delivered')
                ->count(),
            'cancelled_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('status', 'cancelled')
                ->count(),
            'total_revenue' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->where('payment_status', 'paid')
                ->sum('grand_total'),
            'today_orders' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->whereDate('created_at', today())
                ->count(),
            'today_revenue' => \App\Models\Order::where('vendor_id', $vendor->id)
                ->whereDate('created_at', today())
                ->where('payment_status', 'paid')
                ->sum('grand_total'),
            'average_rating' => $vendor->products()->avg('average_rating') ?? 0,
        ];
    }

    public function getAnalytics(User $user): array
    {
        $vendor = $user->vendor;

        if (!$vendor) {
            throw new \Exception('Vendor profile not found');
        }

        $last7Days = collect(range(6, 0))->map(function ($days) {
            return now()->subDays($days)->format('Y-m-d');
        });

        // ✅ REMOVED: orders() relationship - use Order::where('vendor_id', $vendor->id)
        $ordersByDay = \App\Models\Order::where('vendor_id', $vendor->id)
            ->where('created_at', '>=', now()->subDays(7))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(grand_total) as revenue')
            ->groupBy('date')
            ->get()
            ->keyBy('date');

        return [
            'orders_by_day' => $last7Days->map(function ($date) use ($ordersByDay) {
                $data = $ordersByDay->get($date);
                return [
                    'date' => $date,
                    'orders' => $data?->count ?? 0,
                    'revenue' => $data?->revenue ?? 0,
                ];
            })->values()->toArray(),
            'top_products' => $vendor->products()
                ->withCount('orders')
                ->orderBy('orders_count', 'desc')
                ->limit(5)
                ->get(['id', 'name', 'price', 'orders_count']),
        ];
    }
}
