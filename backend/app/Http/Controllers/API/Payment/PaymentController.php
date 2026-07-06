<?php

namespace App\Http\Controllers\API\Payment;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    use ApiResponseTrait;

    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
        $this->middleware('auth:sanctum');
    }

    public function getPaymentStatus($orderId)
    {
        $order = Order::where('id', $orderId)
            ->where('user_id', Auth::id())
            ->first();

        if (!$order) {
            return $this->notFoundResponse('Order not found');
        }

        $status = $this->paymentService->getPaymentStatus($order);
        return $this->successResponse($status, 'Payment status retrieved successfully');
    }

    public function getMyPayments(Request $request)
    {
        $payments = Payment::where('user_id', Auth::id())
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginationResponse($payments, 'Payments retrieved successfully');
    }

    public function getPaymentDetails($paymentId)
    {
        $payment = Payment::where('id', $paymentId)
            ->where('user_id', Auth::id())
            ->with(['order'])
            ->first();

        if (!$payment) {
            return $this->notFoundResponse('Payment not found');
        }

        return $this->successResponse($payment, 'Payment details retrieved successfully');
    }
}