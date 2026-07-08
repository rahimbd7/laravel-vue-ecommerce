<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorPayoutService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VendorPayoutController extends Controller
{
    use ApiResponseTrait;

    protected $vendorPayoutService;

    public function __construct(VendorPayoutService $vendorPayoutService)
    {
        $this->vendorPayoutService = $vendorPayoutService;
        $this->middleware(['auth:sanctum', 'role:vendor']);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $balance = $this->vendorPayoutService->getVendorBalance($vendor->id);

        return $this->successResponse([
            'balance' => $balance,
            'currency' => 'USD',
        ], 'Vendor balance retrieved successfully');
    }

    public function history(Request $request)
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        $history = $this->vendorPayoutService->getVendorPayoutHistory(
            $vendor->id,
            $request->get('per_page', 15)
        );

        return $this->paginationResponse($history, 'Payout history retrieved successfully');
    }

    public function earningsSummary()
    {
        $user = Auth::user();
        $vendor = $user->vendor;

        if (!$vendor) {
            return $this->notFoundResponse('Vendor profile not found');
        }

        // You can add more detailed earnings breakdown here
        $balance = $this->vendorPayoutService->getVendorBalance($vendor->id);

        // Get recent payouts
        $recentPayouts = \App\Models\VendorPayout::where('vendor_id', $vendor->id)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->successResponse([
            'balance' => $balance,
            'recent_payouts' => $recentPayouts,
            'total_orders' => $vendor->orders()->count(),
            'total_products' => $vendor->products()->count(),
        ], 'Earnings summary retrieved successfully');
    }
}