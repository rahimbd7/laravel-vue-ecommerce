<?php

namespace App\Trait;

use App\Models\Order;
use Illuminate\Http\Request;

trait OrderAuthorizationTrait
{
    /**
     * Get orders based on user role
     */
    protected function getOrdersByRole($user, Request $request)
    {
        if ($user->role === 'admin') {
            return Order::with('items', 'user', 'vendor')
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->vendor_id, fn($q) => $q->where('vendor_id', $request->vendor_id))
                ->when($request->from_date, fn($q) => $q->whereDate('created_at', '>=', $request->from_date))
                ->when($request->to_date, fn($q) => $q->whereDate('created_at', '<=', $request->to_date))
                ->latest()
                ->paginate($request->get('per_page', 15));
        }

        if ($user->role === 'vendor') {
            return Order::where('vendor_id', $user->id)
                ->with('items', 'user')
                ->when($request->status, fn($q) => $q->where('status', $request->status))
                ->when($request->payment_status, fn($q) => $q->where('payment_status', $request->payment_status))
                ->latest()
                ->paginate($request->get('per_page', 15));
        }

        // Customer
        return Order::where('user_id', $user->id)
            ->with('items')
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->latest()
            ->paginate($request->get('per_page', 15));
    }

    /**
     * Check if user can view a specific order
     */
    protected function canViewOrder($user, Order $order): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        if ($user->role === 'vendor') {
            return $order->vendor_id === $user->id;
        }

        if ($user->role === 'customer') {
            return $order->user_id === $user->id;
        }

        return false;
    }

    /**
     * Check if user can update an order
     */
    protected function canUpdateOrder($user, Order $order): bool
    {
        // Admin can update any order
        if ($user->role === 'admin') {
            return true;
        }

        // Vendor can update orders from their store
        if ($user->role === 'vendor') {
            return $order->vendor_id === $user->id;
        }

        // Customer can update their own orders under certain conditions
        if ($user->role === 'customer' && $order->user_id === $user->id) {
            return $this->canCustomerUpdateOrder($order);
        }

        return false;
    }

    /**
     * Check if customer can update their order based on order status
     */
    protected function canCustomerUpdateOrder(Order $order): bool
    {
        // Customer can update only if order is in these statuses
        $updatableStatuses = ['pending', 'processing'];

        return in_array($order->status, $updatableStatuses);
    }

    /**
     * Check if user can cancel an order
     */
    protected function canCancelOrder($user, Order $order): bool
    {
        // Admin can cancel any order
        if ($user->role === 'admin') {
            return true;
        }

        // Customer can cancel their own orders
        if ($user->role === 'customer' && $order->user_id === $user->id) {
            return $this->canCustomerCancelOrder($order);
        }

        return false;
    }

    /**
     * Check if customer can cancel their order
     */
    protected function canCustomerCancelOrder(Order $order): bool
    {
        // Customer can cancel only if order is pending or processing
        $cancellableStatuses = ['pending', 'processing'];

        return in_array($order->status, $cancellableStatuses) && $order->isCancellable();
    }

    /**
     * Check if user can create an order
     */
    protected function canCreateOrder($user): bool
    {
        return $user->role === 'customer';
    }

    /**
     * Check if user can view order summary
     */
    protected function canViewSummary($user): bool
    {
        return $user->role === 'customer';
    }

    /**
     * Get allowed update fields based on user role
     */
    protected function getAllowedUpdateFields($user, array $data): array
    {
        if ($user->role === 'admin') {
            return $data;
        }

        if ($user->role === 'vendor') {
            // Vendor can update specific fields
            return array_intersect_key($data, array_flip([
                'status', 'fulfillment_status', 'tracking_number',
                'carrier', 'notes'
            ]));
        }

        // Customer can only update specific fields
        if ($user->role === 'customer') {
            return array_intersect_key($data, array_flip([
                'shipping_address', 'shipping_city', 'shipping_state',
                'shipping_postal_code', 'shipping_country', 'customer_notes'
            ]));
        }

        return [];
    }

    /**
     * Get order summary for customer
     */
    protected function getCustomerOrderSummary($user): array
    {
        return [
            'total_orders' => Order::where('user_id', $user->id)->count(),
            'pending_orders' => Order::where('user_id', $user->id)->where('status', 'pending')->count(),
            'processing_orders' => Order::where('user_id', $user->id)->where('status', 'processing')->count(),
            'completed_orders' => Order::where('user_id', $user->id)->where('status', 'completed')->count(),
            'cancelled_orders' => Order::where('user_id', $user->id)->where('status', 'cancelled')->count(),
            'total_spent' => Order::where('user_id', $user->id)->where('payment_status', 'paid')->sum('grand_total'),
        ];
    }

    /**
     * Get vendor order statistics
     */
    protected function getVendorOrderStats($user): array
    {
        return [
            'total_orders' => Order::where('vendor_id', $user->id)->count(),
            'pending_orders' => Order::where('vendor_id', $user->id)->where('status', 'pending')->count(),
            'processing_orders' => Order::where('vendor_id', $user->id)->where('status', 'processing')->count(),
            'shipped_orders' => Order::where('vendor_id', $user->id)->where('status', 'shipped')->count(),
            'delivered_orders' => Order::where('vendor_id', $user->id)->where('status', 'delivered')->count(),
            'total_revenue' => Order::where('vendor_id', $user->id)->where('payment_status', 'paid')->sum('grand_total'),
        ];
    }
}
