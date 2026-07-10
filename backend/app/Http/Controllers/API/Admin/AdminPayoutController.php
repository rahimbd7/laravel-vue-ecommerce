<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Services\VendorPayoutService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;

class AdminPayoutController extends Controller
{
    use ApiResponseTrait;

    protected $vendorPayoutService;

    public function __construct(VendorPayoutService $vendorPayoutService)
    {
        $this->vendorPayoutService = $vendorPayoutService;
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    public function pendingPayouts(Request $request)
    {
        $payouts = $this->vendorPayoutService->getPendingPayouts(
            $request->get('per_page', 15)
        );

        // Add additional summary data
        $totalPending = \App\Models\VendorPayout::where('status', 'pending')->sum('net_amount');

        return $this->successResponse([
            'payouts' => $payouts,
            'total_pending_amount' => $totalPending,
            'pending_count' => \App\Models\VendorPayout::where('status', 'pending')->count(),
        ], 'Pending payouts retrieved successfully');
    }

    public function processPayouts(Request $request)
    {
        $request->validate([
            'payout_ids' => 'required|array|min:1',
            'payout_ids.*' => 'required|exists:vendor_payouts,id',
            'reference' => 'nullable|string|max:255',
        ]);

        try {
            $this->vendorPayoutService->processPayouts(
                $request->payout_ids,
                $request->reference
            );

            return $this->successResponse(
                null,
                'Payouts processed successfully'
            );
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function payoutSummary()
    {
        $stats = [
            'total_pending' => \App\Models\VendorPayout::where('status', 'pending')->sum('net_amount'),
            'total_processing' => \App\Models\VendorPayout::where('status', 'processing')->sum('net_amount'),
            'total_paid' => \App\Models\VendorPayout::where('status', 'paid')->sum('net_amount'),
            'total_commission' => \App\Models\VendorPayout::sum('commission'),
            'total_platform_fee' => \App\Models\VendorPayout::sum('platform_fee'),
            'pending_count' => \App\Models\VendorPayout::where('status', 'pending')->count(),
            'processing_count' => \App\Models\VendorPayout::where('status', 'processing')->count(),
            'paid_count' => \App\Models\VendorPayout::where('status', 'paid')->count(),
        ];

        return $this->successResponse($stats, 'Payout summary retrieved successfully');
    }

    public function payoutDetails($payoutId)
    {
        $payout = \App\Models\VendorPayout::with(['vendor', 'order'])
            ->find($payoutId);

        if (!$payout) {
            return $this->notFoundResponse('Payout not found');
        }

        return $this->successResponse($payout, 'Payout details retrieved successfully');
    }
}
