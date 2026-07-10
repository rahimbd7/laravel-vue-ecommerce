<?php

namespace App\Services\Admin;

use App\Models\Order;
use App\Models\User;
use App\Models\Vendor;
use App\Models\Payment;
use App\Models\VendorPayout;
use App\Models\TransactionLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminDashboardService
{
    /**
     * Get all dashboard data
     */
    public function getDashboardData(): array
    {
        return [
            'revenue' => $this->getRevenueStats(),
            'users' => $this->getUserStats(),
            'vendors' => $this->getVendorStats(),
            'orders' => $this->getOrderStats(),
            'commission' => $this->getCommissionStats(),
            'activities' => $this->getRecentActivities(),
        ];
    }

    /**
     * Get Revenue Statistics
     */
    public function getRevenueStats(): array
    {
        return Cache::remember('admin_dashboard_revenue', 600, function () {
            // Total Revenue
            $totalRevenue = Order::where('payment_status', 'paid')
                ->whereIn('status', ['delivered', 'completed'])
                ->sum('grand_total') ?? 0;

            // Today's Revenue
            $todayRevenue = Order::where('payment_status', 'paid')
                ->whereIn('status', ['delivered', 'completed'])
                ->whereDate('updated_at', today())
                ->sum('grand_total') ?? 0;

            // This Week Revenue
            $weekRevenue = Order::where('payment_status', 'paid')
                ->whereIn('status', ['delivered', 'completed'])
                ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])
                ->sum('grand_total') ?? 0;

            // This Month Revenue
            $monthRevenue = Order::where('payment_status', 'paid')
                ->whereIn('status', ['delivered', 'completed'])
                ->whereMonth('updated_at', now()->month)
                ->whereYear('updated_at', now()->year)
                ->sum('grand_total') ?? 0;

            // Previous Month Revenue (for growth calculation)
            $previousMonthRevenue = Order::where('payment_status', 'paid')
                ->whereIn('status', ['delivered', 'completed'])
                ->whereMonth('updated_at', now()->subMonth()->month)
                ->whereYear('updated_at', now()->subMonth()->year)
                ->sum('grand_total') ?? 0;

            // Revenue Growth
            $revenueGrowth = 0;
            if ($previousMonthRevenue > 0) {
                $revenueGrowth = (($monthRevenue - $previousMonthRevenue) / $previousMonthRevenue) * 100;
            }

            // Revenue Trend (Last 30 days)
            $revenueTrend = Order::where('payment_status', 'paid')
                ->whereIn('status', ['delivered', 'completed'])
                ->where('updated_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(updated_at) as date'), DB::raw('SUM(grand_total) as revenue'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->toArray();

            return [
                'total' => round($totalRevenue, 2),
                'today' => round($todayRevenue, 2),
                'week' => round($weekRevenue, 2),
                'month' => round($monthRevenue, 2),
                'previous_month' => round($previousMonthRevenue, 2),
                'growth' => round($revenueGrowth, 2),
                'trend' => $revenueTrend,
            ];
        });
    }

    /**
     * Get User Statistics
     */
    public function getUserStats(): array
    {
        return Cache::remember('admin_dashboard_users', 900, function () {
            // Total Users
            $totalUsers = User::count();

            // Today's Users
            $todayUsers = User::whereDate('created_at', today())->count();

            // This Week Users
            $weekUsers = User::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

            // Active Users (logged in within last 30 days)
            $activeUsers = User::where('last_login_at', '>=', now()->subDays(30))->count();

            // Inactive Users (not logged in within last 30 days)
            $inactiveUsers = User::where(function ($query) {
                $query->where('last_login_at', '<', now()->subDays(30))
                    ->orWhereNull('last_login_at');
            })->count();

            // Users by Role
            $usersByRole = User::select('role', DB::raw('COUNT(*) as count'))
                ->groupBy('role')
                ->get()
                ->pluck('count', 'role')
                ->toArray();

            // User Growth Trend (Last 30 days)
            $userTrend = User::where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as new_users'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->toArray();

            return [
                'total' => $totalUsers,
                'today' => $todayUsers,
                'week' => $weekUsers,
                'active' => $activeUsers,
                'inactive' => $inactiveUsers,
                'by_role' => [
                    'customer' => $usersByRole['customer'] ?? 0,
                    'vendor' => $usersByRole['vendor'] ?? 0,
                    'admin' => $usersByRole['admin'] ?? 0,
                ],
                'trend' => $userTrend,
            ];
        });
    }

    /**
     * Get Vendor Statistics
     */
    public function getVendorStats(): array
    {
        return Cache::remember('admin_dashboard_vendors', 900, function () {
            // Total Vendors
            $totalVendors = Vendor::count();

            // Pending Vendors
            $pendingVendors = Vendor::where('is_verified', false)
                ->where('status', 'pending')
                ->count();

            // Verified Vendors
            $verifiedVendors = Vendor::where('is_verified', true)
                ->where('status', 'active')
                ->count();

            // Rejected Vendors
            $rejectedVendors = Vendor::where('status', 'rejected')->count();

            // Suspended Vendors
            $suspendedVendors = Vendor::where('status', 'suspended')->count();

            // Vendors by Status
            $vendorsByStatus = Vendor::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Pending Vendors List (for approval queue)
            $pendingList = Vendor::where('is_verified', false)
                ->where('status', 'pending')
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($vendor) {
                    return [
                        'id' => $vendor->id,
                        'business_name' => $vendor->business_name,
                        'business_email' => $vendor->business_email,
                        'owner_name' => $vendor->user->name ?? 'N/A',
                        'owner_email' => $vendor->user->email ?? 'N/A',
                        'created_at' => $vendor->created_at,
                    ];
                })
                ->toArray();

            // Vendor Growth Trend
            $vendorTrend = Vendor::where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as new_vendors'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->toArray();

            return [
                'total' => $totalVendors,
                'pending' => $pendingVendors,
                'verified' => $verifiedVendors,
                'rejected' => $rejectedVendors,
                'suspended' => $suspendedVendors,
                'by_status' => [
                    'active' => $vendorsByStatus['active'] ?? 0,
                    'pending' => $vendorsByStatus['pending'] ?? 0,
                    'suspended' => $vendorsByStatus['suspended'] ?? 0,
                    'rejected' => $vendorsByStatus['rejected'] ?? 0,
                ],
                'pending_list' => $pendingList,
                'trend' => $vendorTrend,
            ];
        });
    }

    /**
     * Get Order Statistics
     */
    public function getOrderStats(): array
    {
        return Cache::remember('admin_dashboard_orders', 300, function () {
            // Total Orders
            $totalOrders = Order::count();

            // Today's Orders
            $todayOrders = Order::whereDate('created_at', today())->count();

            // This Week Orders
            $weekOrders = Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count();

            // This Month Orders
            $monthOrders = Order::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            // Orders by Status
            $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray();

            // Average Order Value
            $avgOrderValue = Order::where('payment_status', 'paid')
                ->avg('grand_total') ?? 0;

            // Fulfillment Rate
            $deliveredCount = Order::whereIn('status', ['delivered', 'completed'])->count();
            $fulfillmentRate = $totalOrders > 0 ? ($deliveredCount / $totalOrders) * 100 : 0;

            // Recent Orders (last 10)
            $recentOrders = Order::with(['user', 'vendor'])
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($order) {
                    return [
                        'id' => $order->id,
                        'order_number' => $order->order_number,
                        'customer_name' => $order->user->name ?? 'N/A',
                        'vendor_name' => $order->vendor->business_name ?? 'N/A',
                        'grand_total' => $order->grand_total,
                        'status' => $order->status,
                        'payment_status' => $order->payment_status,
                        'created_at' => $order->created_at,
                    ];
                })
                ->toArray();

            // Order Trend (Last 30 days)
            $orderTrend = Order::where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('COUNT(*) as orders'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->toArray();

            return [
                'total' => $totalOrders,
                'today' => $todayOrders,
                'week' => $weekOrders,
                'month' => $monthOrders,
                'by_status' => [
                    'pending' => $ordersByStatus['pending'] ?? 0,
                    'processing' => $ordersByStatus['processing'] ?? 0,
                    'confirmed' => $ordersByStatus['confirmed'] ?? 0,
                    'shipped' => $ordersByStatus['shipped'] ?? 0,
                    'delivered' => $ordersByStatus['delivered'] ?? 0,
                    'completed' => $ordersByStatus['completed'] ?? 0,
                    'cancelled' => $ordersByStatus['cancelled'] ?? 0,
                    'refunded' => $ordersByStatus['refunded'] ?? 0,
                ],
                'avg_order_value' => round($avgOrderValue, 2),
                'fulfillment_rate' => round($fulfillmentRate, 2),
                'recent' => $recentOrders,
                'trend' => $orderTrend,
            ];
        });
    }

    /**
     * Get Commission Statistics
     */
    public function getCommissionStats(): array
    {
        return Cache::remember('admin_dashboard_commission', 900, function () {
            // Total Commission
            $totalCommission = VendorPayout::sum('commission') ?? 0;

            // Pending Commission
            $pendingCommission = VendorPayout::where('status', 'pending')->sum('commission') ?? 0;

            // Paid Commission
            $paidCommission = VendorPayout::where('status', 'paid')->sum('commission') ?? 0;

            // This Month Commission
            $monthCommission = VendorPayout::whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('commission') ?? 0;

            // Previous Month Commission
            $previousMonthCommission = VendorPayout::whereMonth('created_at', now()->subMonth()->month)
                ->whereYear('created_at', now()->subMonth()->year)
                ->sum('commission') ?? 0;

            // Commission Growth
            $commissionGrowth = 0;
            if ($previousMonthCommission > 0) {
                $commissionGrowth = (($monthCommission - $previousMonthCommission) / $previousMonthCommission) * 100;
            }

            // Commission by Vendor (Top 10)
            $commissionByVendor = VendorPayout::with('vendor')
                ->select('vendor_id', DB::raw('SUM(commission) as total_commission'))
                ->groupBy('vendor_id')
                ->orderBy('total_commission', 'desc')
                ->limit(10)
                ->get()
                ->map(function ($item) {
                    return [
                        'vendor_name' => $item->vendor->business_name ?? 'Unknown',
                        'commission' => round($item->total_commission, 2),
                    ];
                })
                ->toArray();

            // Commission Trend (Last 30 days)
            $commissionTrend = VendorPayout::where('created_at', '>=', now()->subDays(30))
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('SUM(commission) as commission'))
                ->groupBy('date')
                ->orderBy('date', 'asc')
                ->get()
                ->toArray();

            return [
                'total' => round($totalCommission, 2),
                'pending' => round($pendingCommission, 2),
                'paid' => round($paidCommission, 2),
                'month' => round($monthCommission, 2),
                'previous_month' => round($previousMonthCommission, 2),
                'growth' => round($commissionGrowth, 2),
                'by_vendor' => $commissionByVendor,
                'trend' => $commissionTrend,
            ];
        });
    }

    /**
     * Get Recent Activities
     */
    public function getRecentActivities(int $limit = 20): array
    {
        return Cache::remember('admin_dashboard_activities', 120, function () use ($limit) {
            $activities = TransactionLog::with('user')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($log) {
                    return [
                        'id' => $log->id,
                        'type' => $log->action,
                        'user_id' => $log->user_id,
                        'user_name' => $log->user->name ?? 'System',
                        'user_role' => $log->user_role ?? 'system',
                        'reference_type' => $log->reference_type,
                        'reference_id' => $log->reference_id,
                        'amount' => $log->amount,
                        'status' => $log->status,
                        'description' => $log->description ?? $this->formatActivityDescription($log),
                        'icon' => $this->getActivityIcon($log->action),
                        'color' => $this->getActivityColor($log->action),
                        'created_at' => $log->created_at,
                        'time_ago' => $log->created_at->diffForHumans(),
                    ];
                })
                ->toArray();

            return [
                'data' => $activities,
                'total' => count($activities),
            ];
        });
    }

    /**
     * Format activity description
     */
    private function formatActivityDescription($log): string
    {
        $userName = $log->user->name ?? 'Unknown User';
        $action = str_replace('_', ' ', $log->action);

        return "{$userName} performed {$action}";
    }

    /**
     * Get activity icon based on action
     */
    private function getActivityIcon(string $action): string
    {
        $icons = [
            'payment' => 'pi pi-credit-card',
            'payout' => 'pi pi-wallet',
            'refund' => 'pi pi-undo',
            'commission' => 'pi pi-percentage',
            'adjustment' => 'pi pi-sliders-h',
            'user_registered' => 'pi pi-user-plus',
            'vendor_applied' => 'pi pi-store',
            'vendor_verified' => 'pi pi-check-circle',
            'order_placed' => 'pi pi-shopping-cart',
            'order_shipped' => 'pi pi-truck',
            'order_delivered' => 'pi pi-check-circle',
            'product_added' => 'pi pi-box',
            'review_submitted' => 'pi pi-star',
        ];

        return $icons[$action] ?? 'pi pi-circle';
    }

    /**
     * Get activity color based on action
     */
    private function getActivityColor(string $action): string
    {
        $colors = [
            'payment' => 'blue',
            'payout' => 'orange',
            'refund' => 'red',
            'commission' => 'purple',
            'adjustment' => 'gray',
            'user_registered' => 'green',
            'vendor_applied' => 'indigo',
            'vendor_verified' => 'green',
            'order_placed' => 'cyan',
            'order_shipped' => 'indigo',
            'order_delivered' => 'green',
            'product_added' => 'teal',
            'review_submitted' => 'yellow',
        ];

        return $colors[$action] ?? 'gray';
    }

    /**
     * Get Top Vendors by Sales
     */
    public function getTopVendors(int $limit = 10): array
    {
        return Cache::remember('admin_dashboard_top_vendors', 600, function () use ($limit) {
            $topVendors = DB::table('vendors')
                ->join('products', 'vendors.id', '=', 'products.vendor_id')
                ->join('order_items', 'products.id', '=', 'order_items.product_id')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('orders.payment_status', 'paid')
                ->whereIn('orders.status', ['delivered', 'completed'])
                ->select(
                    'vendors.id',
                    'vendors.business_name',
                    DB::raw('COUNT(DISTINCT orders.id) as total_orders'),
                    DB::raw('SUM(orders.grand_total) as total_sales'),
                    DB::raw('COUNT(DISTINCT products.id) as total_products')
                )
                ->groupBy('vendors.id', 'vendors.business_name')
                ->orderBy('total_sales', 'desc')
                ->limit($limit)
                ->get()
                ->map(function ($vendor) {
                    return [
                        'id' => $vendor->id,
                        'business_name' => $vendor->business_name,
                        'total_orders' => $vendor->total_orders,
                        'total_sales' => round($vendor->total_sales, 2),
                        'total_products' => $vendor->total_products,
                    ];
                })
                ->toArray();

            return $topVendors;
        });
    }

    /**
     * Clear all dashboard caches
     */
    public function clearCache(): void
    {
        Cache::forget('admin_dashboard_revenue');
        Cache::forget('admin_dashboard_users');
        Cache::forget('admin_dashboard_vendors');
        Cache::forget('admin_dashboard_orders');
        Cache::forget('admin_dashboard_commission');
        Cache::forget('admin_dashboard_activities');
        Cache::forget('admin_dashboard_top_vendors');
    }
}