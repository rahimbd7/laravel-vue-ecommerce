<?php
// app/Http/Resources/Order/OrderItemResource.php

namespace App\Http\Resources\Order;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'product' => [
                'id' => $this->product_id,
                'name' => $this->product_name,
                'sku' => $this->product_sku,
                'variation' => $this->product_variation_name,
                'attributes' => $this->product_attributes,
            ],
            'quantity' => $this->quantity,
            'pricing' => [
                'unit_price' => number_format($this->unit_price, 2),
                'subtotal' => number_format($this->subtotal, 2),
                'discount' => number_format($this->discount, 2),
                'tax' => number_format($this->tax, 2),
                'total' => number_format($this->total, 2),
            ],
            'status' => $this->status,
            'tracking_number' => $this->tracking_number,
            'created_at' => $this->created_at->toISOString(),
        ];
    }
}
