<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorDashboardService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;

class VendorDashboardController extends Controller
{
    use ApiResponseTrait;

    protected $vendorDashboardService;

    public function __construct(VendorDashboardService $vendorDashboardService)
    {
        $this->middleware(['auth:sanctum', 'role:vendor']);
        $this->vendorDashboardService = $vendorDashboardService;
    }

    /**
     * Get vendor dashboard overview
     */
    public function index(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $dashboardData = $this->vendorDashboardService->getVendorDashboard($vendor);

        return $this->successResponse([
            'vendor' => $vendor,
            'stats' => [
                'products_count' => $dashboardData['total_products'] ?? 0,
                'orders_count' => $dashboardData['total_orders'] ?? 0,
                'total_sales' => $dashboardData['total_sales'] ?? 0,
                'pending_orders' => $dashboardData['pending_orders'] ?? 0,
                'processing_orders' => $dashboardData['processing_orders'] ?? 0,
                'shipped_orders' => $dashboardData['shipped_orders'] ?? 0,
                'delivered_orders' => $dashboardData['delivered_orders'] ?? 0,
                'cancelled_orders' => $dashboardData['cancelled_orders'] ?? 0,
                'total_revenue' => $dashboardData['total_revenue'] ?? 0,
                'today_revenue' => $dashboardData['today_revenue'] ?? 0,
                'low_stock_products' => $dashboardData['low_stock_products'] ?? 0,
            ],
            'top_products' => $dashboardData['top_products'] ?? [],
            'recent_orders' => $dashboardData['recent_orders'] ?? [],
        ], 'Vendor Dashboard Data Retrieved Successfully');
    }

    /**
     * Get vendor orders with pagination
     */
    public function orders(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $orders = $this->vendorDashboardService->getVendorOrders($vendor->id, $request->all());

        return $this->paginationResponse($orders, 'Vendor orders retrieved successfully');
    }

    /**
     * Get single order details
     */
    public function showOrder(Request $request, int $orderId)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $order = $this->vendorDashboardService->getVendorOrder($vendor->id, $orderId);

        if (!$order) {
            return $this->notFoundResponse('Order not found or does not belong to you');
        }

        return $this->successResponse($order->load('items'), 'Order retrieved successfully');
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, int $orderId)
    {
        $request->validate([
            'status' => 'required|in:processing,confirmed,shipped',
            'tracking_number' => 'nullable|string|max:255|required_if:status,shipped',
            'carrier' => 'nullable|string|max:255'
        ]);

        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        try {
            $order = $this->vendorDashboardService->updateOrderStatus(
                $vendor->id,
                $orderId,
                $request->only(['status', 'tracking_number', 'carrier'])
            );

            return $this->updatedResponse(
                $order->load('items'),
                'Order status updated successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get vendor order statistics
     */
    public function orderStats(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $stats = $this->vendorDashboardService->getVendorOrderStats($vendor->id);

        return $this->successResponse($stats, 'Vendor order statistics retrieved successfully');
    }

    /**
     * Get recent orders for dashboard
     */
    public function recentOrders(Request $request)
    {
        $vendor = $request->user()->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $limit = $request->get('limit', 10);
        $orders = $this->vendorDashboardService->getRecentOrders($vendor->id, $limit);

        return $this->successResponse($orders, 'Recent orders retrieved successfully');
    }
}
