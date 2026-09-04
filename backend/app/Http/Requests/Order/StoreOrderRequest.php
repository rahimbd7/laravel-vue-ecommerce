<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.product_variation_id' => 'nullable|integer',
            'items.*.quantity' => 'required|integer|min:1|max:100',

            // Customer Information
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'nullable|string|max:20',

            // Billing Address
            'billing_address' => 'required|string|max:500',
            'billing_city' => 'required|string|max:100',
            'billing_state' => 'nullable|string|max:100',
            'billing_postal_code' => 'nullable|string|max:20',
            'billing_country' => 'required|string|size:2',

            // Shipping Address
            'shipping_address' => 'required|string|max:500',
            'shipping_city' => 'required|string|max:100',
            'shipping_state' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:20',
            'shipping_country' => 'required|string|size:2',

            // Shipping Method
            'shipping_method' => 'nullable|string|max:100',

            // Payment
            'payment_method' => 'required|string|in:credit_card,paypal,bank_transfer,cod',

            // Additional
            'customer_notes' => 'nullable|string|max:1000',
            'use_same_address' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Your order must contain at least one item.',
            'items.min' => 'Your order must contain at least one item.',
            'items.*.product_id.required' => 'Product information is missing for one of your items.',
            'items.*.product_id.exists' => 'One of the products in your order is no longer available.',
            'items.*.quantity.required' => 'Please specify the quantity for each product.',
            'items.*.quantity.integer' => 'Product quantity must be a valid number.',
            'items.*.quantity.min' => 'Product quantity must be at least 1.',
            'items.*.quantity.max' => 'Product quantity cannot exceed 100 units.',

            'customer_name.required' => 'Please provide your full name.',
            'customer_email.required' => 'Please provide your email address.',
            'customer_email.email' => 'Please provide a valid email address.',

            'billing_address.required' => 'Billing address is required.',
            'billing_city.required' => 'Billing city is required.',
            'billing_country.required' => 'Billing country is required.',
            'billing_country.size' => 'Please provide a valid 2-letter country code.',

            'shipping_address.required' => 'Shipping address is required.',
            'shipping_city.required' => 'Shipping city is required.',
            'shipping_country.required' => 'Shipping country is required.',

            'payment_method.required' => 'Please select a payment method.',
            'payment_method.in' => 'Selected payment method is not available.',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        // Sanitize error messages - remove array indices
        $errors = [];
        foreach ($validator->errors()->toArray() as $key => $messages) {
            $cleanKey = preg_replace('/\.\d+\./', '.*.', $key);
            $errors[$cleanKey] = $messages;
        }

        throw new HttpResponseException(
            response()->json([
                'status' => 'error',
                'message' => 'Please check your order information and try again.',
                'errors' => $errors
            ], 422)
        );
    }

    protected function prepareForValidation()
    {
        if ($this->input('use_same_address')) {
            $this->merge([
                'shipping_address' => $this->input('billing_address'),
                'shipping_city' => $this->input('billing_city'),
                'shipping_state' => $this->input('billing_state'),
                'shipping_postal_code' => $this->input('billing_postal_code'),
                'shipping_country' => $this->input('billing_country'),
            ]);
        }
    }
}
