<?php
// app/Http/Requests/Product/ProductRequest.php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest {
    public function authorize(): bool {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        // Project currently uses middleware/role checks for product management.
        // Keep request authorization aligned without requiring ProductPolicy.
        if (! $user->isVerifiedVendor()) {
            return false;
        }

        if ($this->isMethod('POST')) {
            return true;
        }

        $product = $this->route('product');
        if (! $product) {
            return false;
        }

        $vendorId = $user->vendor_id ?? $user->vendor?->id;

        return (int) $product->vendor_id === (int) $vendorId;
    }

    public function rules(): array {
        $product   = $this->route('product');
        $productId = $product?->id;
        $isUpdate  = $this->isMethod('PUT') || $this->isMethod('PATCH');

        $rules = [
            // Basic Information
            'name'                => $isUpdate ? 'sometimes|string|max:255' : 'required|string|max:255',
            'slug'                => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('products')->ignore($productId),
            ],
            'description'         => $isUpdate ? 'sometimes|string' : 'required|string',
            'short_description'   => 'nullable|string|max:500',

            // Foreign Keys
            'vendor_id'           => [
                Rule::requiredIf(function () use ($isUpdate) {
                    return ! $isUpdate && ! $this->user()->vendor_id;
                }),
                'integer',
                'exists:vendors,id',
            ],
            'category_id'         => 'nullable|integer|exists:categories,id',

            // Inventory & SKU
            'sku'                 => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('products')->ignore($productId),
            ],
            'stock_quantity'      => 'nullable|integer|min:0',
            'stock_status'        => 'nullable|in:in_stock,out_of_stock,low_stock,backorder',
            'low_stock_threshold' => 'nullable|integer|min:0',

            // Pricing
            'price'               => $isUpdate ? 'sometimes|numeric|min:0' : 'required|numeric|min:0',
            'compare_price'       => 'nullable|numeric|min:0|gt:price',
            'cost_per_item'       => 'nullable|numeric|min:0',

            // Status & Visibility
            'is_visible'          => 'nullable|boolean',
            'is_featured'         => 'nullable|boolean',
            'has_variations'      => 'nullable|boolean',
            'is_taxable'          => 'nullable|boolean',
            'tax_rate'            => 'nullable|numeric|min:0|max:100',

            // Shipping
            'weight'              => 'nullable|numeric|min:0',
            'dimensions'          => 'nullable|string|max:50',
            'shipping_type'       => 'nullable|in:physical,digital,service',
            'free_shipping'       => 'nullable|boolean',

            // SEO
            'meta_title'          => 'nullable|string|max:255',
            'meta_description'    => 'nullable|string',
            'meta_keywords'       => 'nullable|array',
            'meta_keywords.*'     => 'string|max:50',

            // Additional
            'attributes'          => 'nullable|array',
            'tags'                => 'nullable|array',
            'tags.*'              => 'string|max:50',
        ];

        // Image validation for creation
        if ($this->isMethod('POST') && $this->hasFile('images')) {
            $rules['images']   = 'nullable|array|max:10';
            $rules['images.*'] = 'image|mimes:jpeg,png,jpg,gif,webp|max:5120';
        }

        // Variation validation
        if ($this->input('has_variations')) {
            $rules['variations']        = $isUpdate ? 'nullable|array' : 'required|array|min:1';
            $rules['variations.*.name'] = 'required_with:variations|string|max:255';
            $rules['variations.*.sku']  = [
                'required_with:variations',
                'string',
                'max:100',
                Rule::unique('product_variations')->where(function ($query) {
                    return $query->where('product_id', $this->route('product')?->id);
                }),
            ];
            $rules['variations.*.price']          = 'nullable|numeric|min:0';
            $rules['variations.*.compare_price']  = 'nullable|numeric|min:0|gt:variations.*.price';
            $rules['variations.*.stock_quantity'] = 'nullable|integer|min:0';
            $rules['variations.*.attributes']     = 'nullable|array';
            $rules['variations.*.is_default']     = 'nullable|boolean';
            $rules['variations.*.image_id']       = 'nullable|exists:product_images,id';
        }

        return $rules;
    }

    public function messages(): array {
        return [
            'name.required'              => 'Product name is required.',
            'name.max'                   => 'Product name cannot exceed 255 characters.',
            'description.required'       => 'Product description is required.',
            'price.required'             => 'Product price is required.',
            'price.numeric'              => 'Price must be a valid number.',
            'price.min'                  => 'Price cannot be negative.',
            'compare_price.gt'           => 'Compare price must be greater than regular price.',
            'sku.unique'                 => 'This SKU is already in use.',
            'images.*.image'             => 'Each file must be an image.',
            'images.*.max'               => 'Each image must not exceed 5MB.',
            'variations.required'        => 'Variations are required when product has variations.',
            'variations.*.name.required' => 'Each variation must have a name.',
            'variations.*.sku.required'  => 'Each variation must have a SKU.',
            'variations.*.sku.unique'    => 'Variation SKU must be unique.',
        ];
    }

    protected function prepareForValidation() {
        $data = $this->all();

        // Support multipart form-data payload JSON like CategoryApplicationRequest.
        if (isset($data['payload']) && is_string($data['payload'])) {
            $jsonData = json_decode($data['payload'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                $data = array_merge($data, $jsonData);
                unset($data['payload']);
            } else {
                // Keep a flag so validator can return a clear payload-format error.
                $data['__payload_invalid'] = true;
            }
        }

        // Decode JSON-like fields when sent as strings in multipart/form-data
        foreach (['variations', 'attributes', 'meta_keywords', 'tags'] as $jsonField) {
            if (isset($data[$jsonField]) && is_string($data[$jsonField])) {
                $decoded = json_decode($data[$jsonField], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $data[$jsonField] = $decoded;
                }
            }
        }

        $this->merge($data);

        // Set default values only on create.
        // On update, applying defaults can unintentionally overwrite omitted fields
        // and trigger validation errors (e.g. stock_quantity=0 with stock_status=in_stock).
        if ($this->isMethod('POST')) {
            $defaults = [
                'is_visible'          => true,
                'is_featured'         => false,
                'has_variations'      => false,
                'is_taxable'          => true,
                'tax_rate'            => 0,
                'free_shipping'       => false,
                'shipping_type'       => 'physical',
                'stock_status'        => 'in_stock',
                'low_stock_threshold' => 5,
                'stock_quantity'      => 0,
                'meta_keywords'       => [],
                'tags'                => [],
                'attributes'          => [],
            ];

            foreach ($defaults as $key => $value) {
                if (! $this->has($key)) {
                    $this->merge([$key => $value]);
                }
            }
        }

        // Set vendor_id from authenticated user if not provided
        if ($this->isMethod('POST') && ! $this->has('vendor_id') && $this->user() && $this->user()->vendor_id) {
            $this->merge([
                'vendor_id' => $this->user()->vendor_id,
            ]);
        }

        // Convert string booleans
        $booleanFields = ['is_visible', 'is_featured', 'has_variations', 'is_taxable', 'free_shipping'];
        foreach ($booleanFields as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => filter_var($this->$field, FILTER_VALIDATE_BOOLEAN),
                ]);
            }
        }

        // Generate slug from name if not provided
        if ($this->isMethod('POST') && ! $this->has('slug') && $this->has('name')) {
            $this->merge([
                'slug' => \Illuminate\Support\Str::slug($this->name),
            ]);
        }
    }

    protected function passedValidation() {
        // Ensure meta_keywords is array
        if ($this->has('meta_keywords') && is_string($this->meta_keywords)) {
            $this->merge([
                'meta_keywords' => array_map('trim', explode(',', $this->meta_keywords)),
            ]);
        }

        // Ensure tags is array
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => array_map('trim', explode(',', $this->tags)),
            ]);
        }

        // Ensure attributes is array
        if ($this->has('attributes') && is_string($this->attributes)) {
            $this->merge([
                'attributes' => json_decode($this->attributes, true) ?? [],
            ]);
        }
    }

    public function withValidator($validator) {
        $validator->after(function ($validator) {
            if ($this->input('__payload_invalid')) {
                $validator->errors()->add(
                    'payload',
                    'Payload must be valid JSON (no trailing comma, proper quotes, proper brackets).'
                );
            }

            if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
                $updatableFields = [
                    'name', 'slug', 'description', 'short_description',
                    'vendor_id', 'category_id', 'sku',
                    'stock_quantity', 'stock_status', 'low_stock_threshold',
                    'price', 'compare_price', 'cost_per_item',
                    'is_visible', 'is_featured', 'has_variations', 'is_taxable', 'tax_rate',
                    'weight', 'dimensions', 'shipping_type', 'free_shipping',
                    'meta_title', 'meta_description', 'meta_keywords',
                    'attributes', 'tags', 'variations',
                ];

                $hasDataFields = collect($updatableFields)->contains(fn($field) => $this->has($field));
                $hasImageFiles = $this->hasFile('images');

                if (! $hasDataFields && ! $hasImageFiles) {
                    $validator->errors()->add(
                        'update',
                        'No update data provided. Send valid JSON body or valid `payload` JSON in form-data.'
                    );
                }
            }

            // Validate that if has_variations is true, variations are provided
            if ($this->input('has_variations') && empty($this->input('variations'))) {
                $validator->errors()->add('variations', 'Variations are required when product has variations.');
            }

            // Validate that if has_variations is false, variations should be empty
            if (! $this->input('has_variations') && ! empty($this->input('variations'))) {
                $validator->errors()->add('has_variations', 'Product cannot have variations when has_variations is false.');
            }

            // Validate stock status based on quantity only when both are provided.
            if ($this->has('stock_quantity') && $this->has('stock_status')
                && $this->input('stock_quantity') > 0
                && $this->input('stock_status') === 'out_of_stock') {
                $validator->errors()->add('stock_status', 'Stock status cannot be out of stock when quantity is greater than 0.');
            }

            if ($this->has('stock_quantity') && $this->has('stock_status')
                && $this->input('stock_quantity') == 0
                && in_array($this->input('stock_status'), ['in_stock', 'low_stock'])) {
                $validator->errors()->add('stock_status', 'Stock status must be out of stock when quantity is 0.');
            }
        });
    }
}
