<?php
// app/Http/Resources/Product/ProductResource.php

namespace App\Http\Resources\Product;

use App\Http\Resources\CategoryResource;
use App\Http\Resources\Product\ProductVariationResource;
use App\Http\Resources\VendorResource;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'sku' => $this->sku,
            'price' => [
                'original' => $this->price,
                'formatted' => $this->formatted_price,
                'compare' => $this->compare_price ? [
                    'value' => $this->compare_price,
                    'formatted' => '$' . number_format($this->compare_price, 2)
                ] : null,
                'final' => $this->final_price,
                'discount' => $this->discount_percentage,
                'is_on_sale' => $this->is_on_sale,
                'range' => $this->when($this->has_variations, [
                    'min' => $this->getLowestPrice(),
                    'max' => $this->getHighestPrice(),
                ]),
            ],
            'inventory' => [
                'quantity' => $this->stock_quantity,
                'status' => $this->stock_status,
                'status_label' => $this->stock_status_label,
                'threshold' => $this->low_stock_threshold,
                'is_in_stock' => $this->isInStock(),
                'total_stock' => $this->getTotalStock(),
            ],
            'shipping' => [
                'type' => $this->shipping_type,
                'type_label' => $this->shipping_type_label,
                'weight' => $this->weight,
                'weight_unit' => 'kg',
                'dimensions' => $this->dimensions,
                'dimensions_array' => $this->dimensions_array,
                'free_shipping' => $this->free_shipping,
            ],
            'media' => [
                'thumbnail' => $this->thumbnail,
                'image' => $this->image_url,
                'images' => ProductImageResource::collection($this->whenLoaded('images')),
            ],
            'attributes' => $this->attributes,
            'tags' => $this->tags,
            'seo' => [
                'meta_title' => $this->meta_title,
                'meta_description' => $this->meta_description,
                'meta_keywords' => $this->meta_keywords,
            ],
            'stats' => [
                'sold_count' => $this->sold_count,
                'view_count' => $this->view_count,
                'average_rating' => $this->average_rating,
                'review_count' => $this->review_count,
            ],
            'flags' => [
                'is_visible' => $this->is_visible,
                'is_featured' => $this->is_featured,
                'has_variations' => $this->has_variations,
                'is_taxable' => $this->is_taxable,
                'tax_rate' => $this->tax_rate,
                'free_shipping' => $this->free_shipping,
            ],
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'variations' => ProductVariationResource::collection($this->whenLoaded('variations')),
            'reviews' => ProductReviewResource::collection($this->whenLoaded('approvedReviews')),
            'review_stats' => $this->when($request->routeIs('*.show'), function() {
                return [
                    'average' => $this->average_rating,
                    'total' => $this->review_count,
                    'distribution' => [
                        5 => $this->reviews()->where('rating', 5)->count(),
                        4 => $this->reviews()->where('rating', 4)->count(),
                        3 => $this->reviews()->where('rating', 3)->count(),
                        2 => $this->reviews()->where('rating', 2)->count(),
                        1 => $this->reviews()->where('rating', 1)->count(),
                    ],
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'deleted_at' => $this->deleted_at,
        ];
    }
}
