<?php

namespace App\Services;

use App\Models\CommissionSetting;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\TransactionLog;
use App\Models\VendorBalance;
use App\Models\VendorPayout;
use App\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorPayoutService
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function calculateAndCreatePayouts(Order $order): void
    {
        DB::transaction(function () use ($order) {
            // Group order items by vendor
            $itemsByVendor = $order->items->groupBy('product.vendor_id');

            foreach ($itemsByVendor as $vendorId => $items) {
                if (!$vendorId) continue;

                $totalAmount = $items->sum(function ($item) {
                    return $item->unit_price * $item->quantity;
                });

                $commissionRate = CommissionSetting::getRateForVendor($vendorId);
                $commission = ($totalAmount * $commissionRate) / 100;
                $platformFee = $commission; // Platform fee is the commission
                $netAmount = $totalAmount - $commission;

                // Create payout record
                $payout = VendorPayout::create([
                    'order_id' => $order->id,
                    'vendor_id' => $vendorId,
                    'total_amount' => $totalAmount,
                    'commission' => $commission,
                    'net_amount' => $netAmount,
                    'platform_fee' => $platformFee,
                    'status' => 'pending',
                    'notes' => "Payout for order #{$order->order_number}",
                ]);

                // Update vendor balance
                $balance = VendorBalance::firstOrNew(['vendor_id' => $vendorId]);
                $balance->addEarnings($netAmount, $commission);

                // Log transaction
                $vendor = \App\Models\Vendor::find($vendorId);
                if ($vendor && $vendor->user) {
                    $this->transactionService->log(
                        user: $vendor->user,
                        action: 'payout',
                        referenceType: 'vendor_payout',
                        referenceId: $payout->id,
                        amount: $netAmount,
                        status: 'pending',
                        description: "Earnings calculated for order #{$order->order_number}",
                        metadata: [
                            'order_id' => $order->id,
                            'total_amount' => $totalAmount,
                            'commission' => $commission,
                            'commission_rate' => $commissionRate,
                        ]
                    );
                }
            }
        });
    }

    public function processPayouts(array $payoutIds, string $reference = null): void
    {
        DB::transaction(function () use ($payoutIds, $reference) {
            $payouts = VendorPayout::whereIn('id', $payoutIds)
                ->where('status', 'pending')
                ->get();

            foreach ($payouts as $payout) {
                $payout->markAsProcessing();

                // Update vendor balance
                $balance = VendorBalance::where('vendor_id', $payout->vendor_id)->first();
                if ($balance) {
                    $balance->processPayout($payout->net_amount);
                }

                $payout->markAsPaid($reference);

                $this->transactionService->log(
                    user: $payout->vendor->user,
                    action: 'payout',
                    referenceType: 'vendor_payout',
                    referenceId: $payout->id,
                    amount: $payout->net_amount,
                    status: 'paid',
                    description: "Payout processed for order #{$payout->order->order_number}",
                    metadata: [
                        'order_id' => $payout->order_id,
                        'reference' => $reference,
                    ]
                );
            }
        });
    }

    public function getVendorBalance(int $vendorId): array
    {
        $balance = VendorBalance::where('vendor_id', $vendorId)->first();
        
        return [
            'pending_balance' => $balance->pending_balance ?? 0,
            'available_balance' => $balance->available_balance ?? 0,
            'total_earned' => $balance->total_earned ?? 0,
            'total_commission' => $balance->total_commission ?? 0,
        ];
    }

    public function getVendorPayoutHistory(int $vendorId, int $perPage = 15)
    {
        return VendorPayout::where('vendor_id', $vendorId)
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    public function getPendingPayouts(int $perPage = 15)
    {
        return VendorPayout::where('status', 'pending')
            ->with(['vendor', 'order'])
            ->orderBy('created_at', 'asc')
            ->paginate($perPage);
    }
}