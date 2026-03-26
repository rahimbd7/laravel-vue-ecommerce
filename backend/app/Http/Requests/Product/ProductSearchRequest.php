<?php
// app/Http/Requests/Product/ProductSearchRequest.php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class ProductSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search' => 'nullable|string|max:100',
            'category_id' => 'nullable|integer|exists:categories,id',
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'min_price' => 'nullable|numeric|min:0',
            'max_price' => 'nullable|numeric|min:0|gte:min_price',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'in_stock' => 'nullable|boolean',
            'featured' => 'nullable|boolean',
            'has_variations' => 'nullable|boolean',
            'visible' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:price,created_at,name,popularity,rating,newest',
            'sort_order' => 'nullable|string|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',

            // Advanced filters
            'attributes' => 'nullable|array',
            'attributes.*' => 'nullable|string',
            'rating' => 'nullable|integer|min:1|max:5',
            'created_from' => 'nullable|date',
            'created_to' => 'nullable|date|after_or_equal:created_from',
        ];
    }

    public function messages(): array
    {
        return [
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
            'per_page.max' => 'Cannot request more than 100 items per page.',
            'created_to.after_or_equal' => 'End date must be after or equal to start date.',
        ];
    }

    protected function prepareForValidation()
    {
        // Convert string booleans to actual booleans
        if ($this->has('in_stock')) {
            $this->merge([
                'in_stock' => filter_var($this->in_stock, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('featured')) {
            $this->merge([
                'featured' => filter_var($this->featured, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('visible')) {
            $this->merge([
                'visible' => filter_var($this->visible, FILTER_VALIDATE_BOOLEAN)
            ]);
        }

        if ($this->has('has_variations')) {
            $this->merge([
                'has_variations' => filter_var($this->has_variations, FILTER_VALIDATE_BOOLEAN)
            ]);
        }
    }
}
