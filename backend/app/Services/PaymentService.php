<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\TransactionLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PaymentService
{
    protected $transactionService;

    public function __construct(TransactionService $transactionService)
    {
        $this->transactionService = $transactionService;
    }

    public function createPayment(Order $order, array $data): Payment
    {
        return DB::transaction(function () use ($order, $data) {
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => $order->user_id,
                'amount' => $order->grand_total,
                'payment_method' => $data['payment_method'] ?? 'cod',
                'status' => 'pending',
                'metadata' => [
                    'ip_address' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            $this->transactionService->log(
                user: $order->user,
                action: 'payment',
                referenceType: 'payment',
                referenceId: $payment->id,
                amount: $order->grand_total,
                status: 'pending',
                description: "Payment created for order #{$order->order_number}",
                metadata: ['order_id' => $order->id]
            );

            return $payment;
        });
    }

    public function confirmPayment(Payment $payment, array $data = []): Payment
    {
        return DB::transaction(function () use ($payment, $data) {
            $payment->markAsPaid();
            
            if (isset($data['transaction_id'])) {
                $payment->transaction_id = $data['transaction_id'];
                $payment->save();
            }

            // Update order payment status
            $order = $payment->order;
            $order->payment_status = 'paid';
            $order->save();

            $this->transactionService->log(
                user: $payment->user,
                action: 'payment',
                referenceType: 'payment',
                referenceId: $payment->id,
                amount: $payment->amount,
                status: 'success',
                description: "Payment confirmed for order #{$order->order_number}",
                metadata: ['order_id' => $order->id, 'transaction_id' => $payment->transaction_id]
            );

            return $payment;
        });
    }

    public function markPaymentFailed(Payment $payment, string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            $payment->markAsFailed($reason);
            
            $this->transactionService->log(
                user: $payment->user,
                action: 'payment',
                referenceType: 'payment',
                referenceId: $payment->id,
                amount: $payment->amount,
                status: 'failed',
                description: "Payment failed for order #{$payment->order->order_number}: {$reason}",
                metadata: ['order_id' => $payment->order_id, 'reason' => $reason]
            );

            return $payment;
        });
    }

    public function refundPayment(Payment $payment, string $reason = null): Payment
    {
        return DB::transaction(function () use ($payment, $reason) {
            $payment->status = 'refunded';
            $payment->save();

            $order = $payment->order;
            $order->payment_status = 'refunded';
            $order->save();

            $this->transactionService->log(
                user: $payment->user,
                action: 'refund',
                referenceType: 'payment',
                referenceId: $payment->id,
                amount: $payment->amount,
                status: 'success',
                description: "Payment refunded for order #{$order->order_number}: {$reason}",
                metadata: ['order_id' => $order->id, 'reason' => $reason]
            );

            return $payment;
        });
    }

    public function getPaymentStatus(Order $order): array
    {
        $payment = $order->payments()->latest()->first();
        return [
            'status' => $payment?->status ?? 'no_payment',
            'method' => $payment?->payment_method ?? null,
            'amount' => $payment?->amount ?? 0,
            'paid_at' => $payment?->paid_at,
        ];
    }
}