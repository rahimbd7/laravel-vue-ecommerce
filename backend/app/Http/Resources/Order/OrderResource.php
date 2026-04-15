<?php
// app/Http/Resources/Order/OrderResource.php

namespace App\Http\Resources\Order;
use App\Http\Resources\Order\OrderItemResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'order_number' => $this->order_number,
            'invoice_number' => $this->invoice_number,

            'status' => [
                'order' => $this->status,
                'payment' => $this->payment_status,
                'fulfillment' => $this->fulfillment_status,
            ],

            'pricing' => [
                'subtotal' => number_format($this->subtotal, 2),
                'discount_total' => number_format($this->discount_total, 2),
                'tax_total' => number_format($this->tax_total, 2),
                'shipping_total' => number_format($this->shipping_total, 2),
                'grand_total' => number_format($this->grand_total, 2),
            ],

            'customer' => [
                'id' => $this->user_id,
                'name' => $this->customer_name,
                'email' => $this->customer_email,
                'phone' => $this->customer_phone,
            ],

            'addresses' => [
                'billing' => [
                    'address' => $this->billing_address,
                    'city' => $this->billing_city,
                    'state' => $this->billing_state,
                    'postal_code' => $this->billing_postal_code,
                    'country' => $this->billing_country,
                ],
                'shipping' => [
                    'address' => $this->shipping_address,
                    'city' => $this->shipping_city,
                    'state' => $this->shipping_state,
                    'postal_code' => $this->shipping_postal_code,
                    'country' => $this->shipping_country,
                ],
            ],

            'items' => OrderItemResource::collection($this->whenLoaded('items')),

            'shipping' => [
                'method' => $this->shipping_method,
                'tracking_number' => $this->tracking_number,
                'carrier' => $this->carrier,
                'shipped_at' => $this->shipped_at?->toISOString(),
                'delivered_at' => $this->delivered_at?->toISOString(),
            ],

            'payment' => [
                'method' => $this->payment_method,
                'transaction_id' => $this->payment_transaction_id,
                'paid_at' => $this->paid_at?->toISOString(),
            ],

            'notes' => [
                'customer_notes' => $this->customer_notes,
                'admin_notes' => $this->notes,
            ],

            'timestamps' => [
                'created_at' => $this->created_at->toISOString(),
                'updated_at' => $this->updated_at->toISOString(),
            ],

            'cancellation' => $this->cancelled_at ? [
                'cancelled_at' => $this->cancelled_at->toISOString(),
                'reason' => $this->cancellation_reason,
            ] : null,
        ];
    }
}
