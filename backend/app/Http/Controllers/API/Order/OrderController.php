<?php
// app/Http/Controllers/API/Order/OrderController.php

namespace App\Http\Controllers\API\Order;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateOrderRequest;
use App\Http\Resources\Order\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use App\Trait\ApiResponseTrait;
use App\Trait\OrderAuthorizationTrait;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    use ApiResponseTrait, OrderAuthorizationTrait;

    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    /**
     * Display a listing of orders based on user role.
     */
    public function index(Request $request)
    {
        $orders = $this->getOrdersByRole($request->user(), $request);
        return $this->paginationResponse($orders, "Orders retrieved successfully");
    }

    /**
     * Store a newly created order.
     */
    public function store(StoreOrderRequest $request)
    {
        if (!$this->canCreateOrder($request->user())) {
            return $this->forbiddenResponse("Only customers can create orders");
        }

        try {
            $order = $this->orderService->createOrder($request->validated());
            return $this->createResponse(
                new OrderResource($order->load('items')),
                "Order created successfully"
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Failed to create order: " . $e->getMessage());
        }
    }

    /**
     * Display the specified order.
     */
    public function show(Request $request, Order $order)
    {
        if (!$this->canViewOrder($request->user(), $order)) {
            return $this->forbiddenResponse("You are not authorized to view this order");
        }

        $order->load('items');

        // Load additional relations based on role
        if ($request->user()->role === 'admin') {
            $order->load('user', 'vendor');
        }

        return $this->successResponse(
            new OrderResource($order),
            "Order retrieved successfully"
        );
    }

    /**
     * Update the specified order.
     */
    public function update(UpdateOrderRequest $request, Order $order)
    {
        $user = $request->user();

        if (!$this->canUpdateOrder($user, $order)) {
            // Provide specific error message based on role and order status
            if ($user->role === 'customer') {
                if ($order->user_id !== $user->id) {
                    return $this->forbiddenResponse("You can only update your own orders.");
                }

                if (!in_array($order->status, ['pending', 'processing'])) {
                    return $this->forbiddenResponse(
                        "You cannot update this order because it has already been {$order->status}. " .
                        "Please contact support if you need assistance."
                    );
                }
            }

            return $this->forbiddenResponse("You are not authorized to update this order.");
        }

        try {
            $allowedData = $this->getAllowedUpdateFields($user, $request->validated());
            $updatedOrder = $this->orderService->updateOrderStatus($order, $allowedData);

            $message = $user->role === 'customer'
                ? "Your order has been updated successfully."
                : "Order updated successfully";

            return $this->updatedResponse(
                new OrderResource($updatedOrder->load('items')),
                $message
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Failed to update order: " . $e->getMessage());
        }
    }

    /**
     * Cancel the specified order.
     */
    public function cancel(Request $request, Order $order)
    {
        if (!$this->canCancelOrder($request->user(), $order)) {
            return $this->forbiddenResponse("You are not authorized to cancel this order");
        }

        try {
            $request->validate([
                'cancellation_reason' => 'required|string|max:500'
            ]);

            $updatedOrder = $this->orderService->updateOrderStatus($order, [
                'status' => 'cancelled',
                'cancellation_reason' => $request->cancellation_reason,
                'cancelled_at' => now(),
            ]);

            return $this->updatedResponse(
                new OrderResource($updatedOrder),
                "Order cancelled successfully"
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), "Validation failed");
        } catch (\Exception $e) {
            return $this->serverErrorResponse("Failed to cancel order: " . $e->getMessage());
        }
    }

    /**
     * Get order summary for the authenticated user.
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'customer') {
            $summary = $this->getCustomerOrderSummary($user);
            return $this->successResponse($summary, "Order summary retrieved successfully");
        }

        if ($user->role === 'vendor') {
            $stats = $this->getVendorOrderStats($user);
            return $this->successResponse($stats, "Order statistics retrieved successfully");
        }

        if ($user->role === 'admin') {
            $stats = [
                'total_orders' => Order::count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('grand_total'),
                'pending_orders' => Order::where('status', 'pending')->count(),
                'today_orders' => Order::whereDate('created_at', today())->count(),
                'today_revenue' => Order::whereDate('created_at', today())->where('payment_status', 'paid')->sum('grand_total'),
            ];
            return $this->successResponse($stats, "Admin statistics retrieved successfully");
        }

        return $this->forbiddenResponse("You are not authorized to view summary");
    }
}
