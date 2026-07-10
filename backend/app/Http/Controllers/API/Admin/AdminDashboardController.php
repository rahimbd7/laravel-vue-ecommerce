<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboardService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    use ApiResponseTrait;

    protected $dashboardService;

    public function __construct(AdminDashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * Get all dashboard data
     * GET /api/admin/dashboard
     */
    public function index()
    {
        try {
            $data = $this->dashboardService->getDashboardData();
            return $this->successResponse($data, 'Dashboard data retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get revenue statistics
     * GET /api/admin/dashboard/revenue
     */
    public function revenue()
    {
        try {
            $data = $this->dashboardService->getRevenueStats();
            return $this->successResponse($data, 'Revenue statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get user statistics
     * GET /api/admin/dashboard/users
     */
    public function users()
    {
        try {
            $data = $this->dashboardService->getUserStats();
            return $this->successResponse($data, 'User statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get vendor statistics
     * GET /api/admin/dashboard/vendors
     */
    public function vendors()
    {
        try {
            $data = $this->dashboardService->getVendorStats();
            return $this->successResponse($data, 'Vendor statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get order statistics
     * GET /api/admin/dashboard/orders
     */
    public function orders()
    {
        try {
            $data = $this->dashboardService->getOrderStats();
            return $this->successResponse($data, 'Order statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get commission statistics
     * GET /api/admin/dashboard/commissions
     */
    public function commissions()
    {
        try {
            $data = $this->dashboardService->getCommissionStats();
            return $this->successResponse($data, 'Commission statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get recent activities
     * GET /api/admin/dashboard/activities
     */
    public function activities(Request $request)
    {
        try {
            $limit = $request->get('limit', 20);
            $data = $this->dashboardService->getRecentActivities($limit);
            return $this->successResponse($data, 'Recent activities retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Get top vendors
     * GET /api/admin/dashboard/top-vendors
     */
    public function topVendors(Request $request)
    {
        try {
            $limit = $request->get('limit', 10);
            $data = $this->dashboardService->getTopVendors($limit);
            return $this->successResponse($data, 'Top vendors retrieved successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }

    /**
     * Clear dashboard cache
     * POST /api/admin/dashboard/clear-cache
     */
    public function clearCache()
    {
        try {
            $this->dashboardService->clearCache();
            return $this->successResponse(null, 'Dashboard cache cleared successfully');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 500);
        }
    }
}