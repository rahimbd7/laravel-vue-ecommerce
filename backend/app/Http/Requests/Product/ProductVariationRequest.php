<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariationRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $product = $this->route('product');
        $variation = $this->route('variation');
        $variationId = $variation?->id;
        $actionMethod = $this->route()?->getActionMethod();

        // Action-specific rules first (custom endpoints)
        if ($actionMethod === 'bulkUpdate') {
            return [
                'variations' => 'required|array',
                'variations.*.id' => 'sometimes|exists:product_variations,id,product_id,' . $product->id,
                'variations.*.name' => 'required_without:variations.*.id|string|max:255',
                'variations.*.sku' => 'required_without:variations.*.id|string|max:100',
                'variations.*.price' => 'nullable|numeric|min:0',
                'variations.*.stock_quantity' => 'integer|min:0',
            ];
        }

        if ($actionMethod === 'updateStock') {
            return [
                'quantity' => 'required|integer|min:0',
                'operation' => 'required|in:set,increment,decrement',
            ];
        }

        if ($actionMethod === 'reorder') {
            return [
                'order' => 'required|array|min:1',
                'order.*' => 'integer|distinct',
            ];
        }

        if ($actionMethod === 'setDefault') {
            return [];
        }

        $isUpdate = in_array($actionMethod, ['update'], true) || $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            'name' => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'sku' => [
                $isUpdate ? 'sometimes' : 'required',
                'string',
                'max:100',
                Rule::unique('product_variations')->ignore($variationId)->where(function ($query) use ($product) {
                    return $query->where('product_id', $product->id);
                }),
            ],
            'barcode' => 'nullable|string|max:100',
            'attributes' => 'nullable|array',
            'price' => 'nullable|numeric|min:0',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'cost_per_item' => 'nullable|numeric|min:0',
            'stock_quantity' => 'nullable|integer|min:0',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'stock_status' => 'nullable|in:in_stock,out_of_stock,low_stock,backorder',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:50',
            'is_visible' => 'nullable|boolean',
            'is_default' => 'nullable|boolean',
            'position' => 'nullable|integer|min:0',
            'image_id' => 'nullable|exists:product_images,id',
        ];

        return $rules;
    }
}
