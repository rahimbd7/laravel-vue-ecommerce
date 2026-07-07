<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;

class VendorDashboardService
{
    /**
     * Get vendor dashboard data
     */
    public function getVendorDashboard(Vendor $vendor): array
    {
        return [
            'total_orders' => $this->getTotalOrders($vendor->id),
            'total_sales' => $this->getTotalSales($vendor->id),
            'pending_orders' => $this->getPendingOrders($vendor->id),
            'processing_orders' => $this->getProcessingOrders($vendor->id),
            'shipped_orders' => $this->getShippedOrders($vendor->id),
            'delivered_orders' => $this->getDeliveredOrders($vendor->id),
            'cancelled_orders' => $this->getCancelledOrders($vendor->id),
            'total_products' => $this->getTotalProducts($vendor->id),
            'low_stock_products' => $this->getLowStockProducts($vendor->id),
            'total_revenue' => $this->getTotalRevenue($vendor->id),
            'today_revenue' => $this->getTodayRevenue($vendor->id),
            'top_products' => $this->getTopProducts($vendor->id),
            'recent_orders' => $this->getRecentOrders($vendor->id, 5),
        ];
    }

    /**
     * Get vendor orders with filters
     */
    public function getVendorOrders(int $vendorId, array $filters = [])
    {
        $query = Order::where('vendor_id', $vendorId)
            ->with(['user', 'items'])
            ->orderBy('created_at', 'desc');

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        if (!empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (!empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        if (!empty($filters['search'])) {
            $query->where(function($q) use ($filters) {
                $q->where('order_number', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_name', 'like', '%' . $filters['search'] . '%')
                  ->orWhere('customer_email', 'like', '%' . $filters['search'] . '%');
            });
        }

        $perPage = $filters['per_page'] ?? 15;
        return $query->paginate($perPage);
    }

    /**
     * Get single order for vendor
     */
    public function getVendorOrder(int $vendorId, int $orderId)
    {
        return Order::where('id', $orderId)
            ->where('vendor_id', $vendorId)
            ->with(['user', 'items'])
            ->first();
    }

    /**
     * Update order status for vendor
     */
    public function updateOrderStatus(int $vendorId, int $orderId, array $data)
    {
        return DB::transaction(function () use ($vendorId, $orderId, $data) {
            $order = Order::where('id', $orderId)
                ->where('vendor_id', $vendorId)
                ->first();

            if (!$order) {
                throw new \Exception('Order not found or does not belong to you');
            }

            if (in_array($order->status, ['shipped', 'delivered', 'completed', 'cancelled'])) {
                throw new \Exception('Cannot update order that has been shipped or delivered');
            }

            $validTransitions = [
                'pending' => ['processing'],
                'processing' => ['confirmed'],
                'confirmed' => ['shipped'],
            ];

            if (isset($data['status'])) {
                $newStatus = $data['status'];
                $currentStatus = $order->status;

                if (isset($validTransitions[$currentStatus])) {
                    if (!in_array($newStatus, $validTransitions[$currentStatus])) {
                        throw new \Exception("Cannot change status from {$currentStatus} to {$newStatus}");
                    }
                } else {
                    throw new \Exception("Cannot update order with status: {$currentStatus}");
                }

                $order->status = $newStatus;

                if ($newStatus === 'shipped') {
                    $order->shipped_at = now();
                }
            }

            if (isset($data['tracking_number'])) {
                $order->tracking_number = $data['tracking_number'];
            }

            if (isset($data['carrier'])) {
                $order->carrier = $data['carrier'];
            }

            $order->save();

            return $order->fresh();
        });
    }

    /**
     * Get vendor order statistics
     */
    public function getVendorOrderStats(int $vendorId): array
    {
        return [
            'total_orders' => $this->getTotalOrders($vendorId),
            'pending_orders' => $this->getPendingOrders($vendorId),
            'processing_orders' => $this->getProcessingOrders($vendorId),
            'confirmed_orders' => $this->getConfirmedOrders($vendorId),
            'shipped_orders' => $this->getShippedOrders($vendorId),
            'delivered_orders' => $this->getDeliveredOrders($vendorId),
            'cancelled_orders' => $this->getCancelledOrders($vendorId),
            'total_revenue' => $this->getTotalRevenue($vendorId),
            'today_orders' => $this->getTodayOrders($vendorId),
            'today_revenue' => $this->getTodayRevenue($vendorId),
            'total_products' => $this->getTotalProducts($vendorId),
            'low_stock_products' => $this->getLowStockProducts($vendorId),
        ];
    }

    /**
     * Get recent orders for dashboard
     */
    public function getRecentOrders(int $vendorId, int $limit = 10)
    {
        return Order::where('vendor_id', $vendorId)
            ->with(['user', 'items'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // ===================== PRIVATE HELPER METHODS =====================

    private function getTotalOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)->count();
    }

    private function getPendingOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->where('status', 'pending')
            ->count();
    }

    private function getProcessingOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->where('status', 'processing')
            ->count();
    }

    private function getConfirmedOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->where('status', 'confirmed')
            ->count();
    }

    private function getShippedOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->where('status', 'shipped')
            ->count();
    }

    private function getDeliveredOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->where('status', 'delivered')
            ->count();
    }

    private function getCancelledOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->where('status', 'cancelled')
            ->count();
    }

    private function getTotalRevenue(int $vendorId): float
    {
        return Order::where('vendor_id', $vendorId)
            ->where('payment_status', 'paid')
            ->sum('grand_total') ?? 0;
    }

    private function getTodayOrders(int $vendorId): int
    {
        return Order::where('vendor_id', $vendorId)
            ->whereDate('created_at', today())
            ->count();
    }

    private function getTodayRevenue(int $vendorId): float
    {
        return Order::where('vendor_id', $vendorId)
            ->whereDate('created_at', today())
            ->where('payment_status', 'paid')
            ->sum('grand_total') ?? 0;
    }

    private function getTotalSales(int $vendorId): float
    {
        return Order::where('vendor_id', $vendorId)
            ->sum('grand_total') ?? 0;
    }

    private function getTotalProducts(int $vendorId): int
    {
        return Product::where('vendor_id', $vendorId)->count();
    }

    private function getLowStockProducts(int $vendorId): int
    {
        return Product::where('vendor_id', $vendorId)
            ->where('stock_quantity', '<=', 10)
            ->count();
    }

    private function getTopProducts(int $vendorId, int $limit = 5): array
    {
        return DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.name',
                DB::raw('SUM(order_items.quantity) as total_sold'),
                DB::raw('SUM(order_items.total) as total_revenue')
            )
            ->where('products.vendor_id', $vendorId)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_sold', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }
}
