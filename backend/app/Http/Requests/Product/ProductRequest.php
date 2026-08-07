<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            // 🔴 REQUIRED - User must provide
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'stock_quantity' => 'required|integer|min:0',
            'stock_status' => 'required|in:in_stock,out_of_stock,low_stock,backorder',

            // 🟡 RECOMMENDED - Nice to have
            'short_description' => 'nullable|string|max:500',
            'compare_price' => 'nullable|numeric|min:0|gt:price',
            'status' => 'required|in:active,inactive,draft',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|string|max:50',
            'images' => 'nullable|array',
            'images.*' => 'image|mimes:jpeg,png,jpg|max:2048',
            'is_visible' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',

            // 🟢 AUTO-GENERATED - Never required
            'slug' => 'nullable|string|max:255|unique:products,slug,' . $productId,
            'sku' => 'nullable|string|max:100|unique:products,sku,' . $productId,
            'meta_title' => 'nullable|string|max:60',
            'meta_description' => 'nullable|string|max:160',
            'meta_keywords' => 'nullable|string|max:255',

            // ✅ Images - Handle both file uploads and Cloudinary URLs
            'images' => 'nullable|array',
            'images.*' => 'array', // Each image is an array
            'images.*.secure_url' => 'required_with:images|string|url',
            'images.*.public_id' => 'required_with:images|string',
            'images.*.is_primary' => 'sometimes|boolean',
            'images.*.thumbnail' => 'nullable|string|url',
            'images.*.medium' => 'nullable|string|url',
            'images.*.large' => 'nullable|string|url',

            // Optional
            'is_taxable' => 'sometimes|boolean',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'free_shipping' => 'sometimes|boolean',
            'low_stock_threshold' => 'nullable|integer|min:0',
            'variations' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            // Required messages
            'name.required' => 'Product name is required',
            'description.required' => 'Product description is required',
            'price.required' => 'Price is required',
            'category_id.required' => 'Category is required',
            'stock_quantity.required' => 'Stock quantity is required',
            'stock_status.required' => 'Stock status is required',

            // Validation messages
            'name.max' => 'Product name must not exceed 255 characters',
            'price.numeric' => 'Price must be a valid number',
            'price.min' => 'Price must be at least 0',
            'compare_price.gt' => 'Compare price must be greater than regular price',
            'category_id.exists' => 'Selected category does not exist',
            'stock_quantity.integer' => 'Stock quantity must be a whole number',
            'stock_status.in' => 'Invalid stock status',
            'sku.unique' => 'This SKU is already in use',
            'slug.unique' => 'This slug is already in use',
            'meta_title.max' => 'Meta title must not exceed 60 characters',
            'meta_description.max' => 'Meta description must not exceed 160 characters',
            'images.*.secure_url.required_with' => 'Image URL is required',
            'images.*.secure_url.url' => 'Invalid image URL format',
            'images.*.public_id.required_with' => 'Image public ID is required',
        ];
    }
}
