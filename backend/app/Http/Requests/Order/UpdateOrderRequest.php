<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $user = $this->user();
        $order = $this->route('order');

        // Admin can update everything
        if ($user->role === 'admin') {
            return [
                'status' => ['sometimes', Rule::in([
                    'pending', 'processing', 'confirmed', 'shipped',
                    'delivered', 'completed', 'cancelled', 'refunded'
                ])],
                'payment_status' => ['sometimes', Rule::in([
                    'pending', 'paid', 'failed', 'refunded', 'partially_refunded'
                ])],
                'fulfillment_status' => ['sometimes', Rule::in([
                    'unfulfilled', 'partially_fulfilled', 'fulfilled', 'shipped', 'delivered'
                ])],
                'tracking_number' => 'nullable|string|max:255',
                'carrier' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000',
                'cancellation_reason' => 'required_if:status,cancelled|nullable|string|max:500',
            ];
        }

        // Vendor can update specific fields
        if ($user->role === 'vendor') {
            return [
                'status' => ['sometimes', Rule::in([
                    'processing', 'confirmed', 'shipped', 'delivered', 'completed'
                ])],
                'fulfillment_status' => ['sometimes', Rule::in([
                    'fulfilled', 'shipped', 'delivered'
                ])],
                'tracking_number' => 'nullable|string|max:255',
                'carrier' => 'nullable|string|max:100',
                'notes' => 'nullable|string|max:1000',
            ];
        }

        // Customer can only update shipping address and notes
        if ($user->role === 'customer' && $order && $order->user_id === $user->id) {
            // Only allow updates if order is pending or processing
            if (in_array($order->status, ['pending', 'processing'])) {
                return [
                    'shipping_address' => 'sometimes|string|max:500',
                    'shipping_city' => 'sometimes|string|max:100',
                    'shipping_state' => 'nullable|string|max:100',
                    'shipping_postal_code' => 'nullable|string|max:20',
                    'shipping_country' => 'sometimes|string|size:2',
                    'customer_notes' => 'nullable|string|max:1000',
                ];
            }
        }

        return [];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid order status.',
            'payment_status.in' => 'Invalid payment status.',
            'fulfillment_status.in' => 'Invalid fulfillment status.',
            'cancellation_reason.required_if' => 'Please provide a reason for cancellation.',
        ];
    }
}
