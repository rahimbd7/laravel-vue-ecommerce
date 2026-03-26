<?php
// app/Http/Resources/Product/ProductCollectionResource.php

namespace App\Http\Resources\Product;

use App\Http\Resources\Product\ProductResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class ProductCollectionResource extends ResourceCollection
{
    public $collects = ProductResource::class;

    public function toArray($request)
    {
        return [
            'data' => $this->collection,
            'pagination' => [
                'total' => $this->total(),
                'count' => $this->count(),
                'per_page' => $this->perPage(),
                'current_page' => $this->currentPage(),
                'total_pages' => $this->lastPage(),
                'next_page_url' => $this->nextPageUrl(),
                'prev_page_url' => $this->previousPageUrl(),
                'first_page_url' => $this->url(1),
                'last_page_url' => $this->url($this->lastPage()),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
            'meta' => [
                'filters' => $this->getFilters(),
                'sort' => [
                    'by' => request('sort_by', 'created_at'),
                    'order' => request('sort_order', 'desc'),
                ],
                'total_value' => $this->collection->sum(function($product) {
                    return $product->price * $product->stock_quantity;
                }),
                'average_price' => $this->collection->avg('price'),
            ]
        ];
    }

    protected function getFilters(): array
    {
        return array_filter(request()->only([
            'search',
            'category_id',
            'vendor_id',
            'min_price',
            'max_price',
            'in_stock',
            'featured',
            'has_variations',
            'rating',
        ]));
    }

    public function with($request)
    {
        return [
            'success' => true,
            'message' => 'Products retrieved successfully',
        ];
    }

    public function withResponse($request, $response)
    {
        $jsonResponse = json_decode($response->getContent(), true);
        unset($jsonResponse['links'], $jsonResponse['meta']);

        $response->setContent(json_encode([
            'success' => true,
            'data' => $jsonResponse['data'] ?? [],
            'pagination' => $jsonResponse['pagination'] ?? [],
            'meta' => $jsonResponse['meta'] ?? [],
            'message' => 'Products retrieved successfully'
        ]));
    }
}
