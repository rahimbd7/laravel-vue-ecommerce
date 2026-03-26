<?php
// app/Http/Resources/Product/ProductVariationResource.php

namespace App\Http\Resources\Product;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductVariationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'attributes' => $this->attributes,
            'price' => [
                'original' => $this->price,
                'formatted' => $this->formatted_price,
                'compare' => $this->compare_price ? [
                    'value' => $this->compare_price,
                    'formatted' => '$' . number_format($this->compare_price, 2)
                ] : null,
                'final' => $this->final_price,
                'discount' => $this->discount_percentage,
                'is_on_sale' => $this->is_on_sale
            ],
            'inventory' => [
                'quantity' => $this->stock_quantity,
                'status' => $this->stock_status,
                'status_label' => $this->stock_status_label,
                'threshold' => $this->low_stock_threshold,
                'is_in_stock' => $this->isInStock(),
            ],
            'shipping' => [
                'weight' => $this->weight,
                'weight_unit' => 'kg',
                'dimensions' => $this->dimensions,
            ],
            'image' => new ProductImageResource($this->whenLoaded('image')),
            'flags' => [
                'is_visible' => $this->is_visible,
                'is_default' => $this->is_default,
                'position' => $this->position,
            ],
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
