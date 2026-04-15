<?php
// app/Http/Controllers/API/Checkout/CheckoutController.php

namespace App\Http\Controllers\API\Checkout;

use App\Http\Controllers\Controller;
use App\Services\CheckoutService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    use ApiResponseTrait;

    protected $checkoutService;

    public function __construct(CheckoutService $checkoutService)
    {
        $this->checkoutService = $checkoutService;
    }

    /**
     * GET /checkout/summary - Shows cart items and address status
     */
    public function summary()
    {
        try {
            $summary = $this->checkoutService->getCheckoutSummary();

            $user = Request::user();
            $profile = $user->profile;

            $hasAddress = $profile && !empty($profile->address);

            $summary['address_status'] = [
                'has_address' => $hasAddress,
                'requires_address_input' => !$hasAddress,
                'message' => $hasAddress
                    ? 'Your saved address will be used. Update below if needed.'
                    : 'Please add your shipping address to continue.'
            ];

            if ($hasAddress) {
                $summary['user_address'] = [
                    'address' => $profile->address,
                    'phone' => $profile->phone,
                ];
            }

            return $this->successResponse($summary, "Checkout summary retrieved");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * POST /checkout/process - Places order (and optionally saves address)
     */
    public function process(Request $request)
    {
        try {
            $user = Request::user();
            $profile = $user->profile;
            $hasAddress = $profile && !empty($profile->address);

            // If user has NO saved address, they MUST provide it
            if (!$hasAddress) {
                $request->validate([
                    'shipping_address' => 'required|string|max:500',
                    'shipping_city' => 'required|string|max:100',
                    'shipping_country' => 'required|string|size:2',
                    'billing_address' => 'required|string|max:500',
                    'billing_city' => 'required|string|max:100',
                    'billing_country' => 'required|string|size:2',
                    'payment_method' => 'required|in:cod,bank_transfer,credit_card,paypal',
                    'shipping_method' => 'nullable|in:standard,express,overnight',
                    'save_address' => 'boolean',
                ]);
            } else {
                // User has address - can checkout with minimal data
                $request->validate([
                    'payment_method' => 'required|in:cod,bank_transfer,credit_card,paypal',
                    'shipping_method' => 'nullable|in:standard,express,overnight',
                    'notes' => 'nullable|string|max:1000',
                    // Optional: User can update address during checkout
                    'shipping_address' => 'nullable|string|max:500',
                    'shipping_city' => 'nullable|string|max:100',
                    'shipping_country' => 'nullable|string|size:2',
                    'save_address' => 'boolean',
                ]);
            }

            // Handle address: use saved or new, and save if requested
            $shippingAddress = null;
            $shippingCity = null;
            $shippingCountry = null;
            $billingAddress = null;
            $billingCity = null;
            $billingCountry = null;

            if ($hasAddress && empty($request->shipping_address)) {
                // Use saved address
                $shippingAddress = $profile->address;
                $billingAddress = $profile->address;
            } else {
                // Use provided address
                $shippingAddress = $request->shipping_address;
                $shippingCity = $request->shipping_city;
                $shippingCountry = $request->shipping_country;
                $billingAddress = $request->billing_address ?? $request->shipping_address;
                $billingCity = $request->billing_city ?? $request->shipping_city;
                $billingCountry = $request->billing_country ?? $request->shipping_country;

                // Save address to profile if requested
                if ($request->save_address) {
                    $profileData = [
                        'address' => $shippingAddress,
                        'phone' => $request->customer_phone ?? $profile->phone ?? null,
                    ];

                    if ($profile) {
                        $profile->update($profileData);
                    } else {
                        $user->profile()->create($profileData);
                    }
                }
            }

            // Merge address data for order creation
            $request->merge([
                'shipping_address' => $shippingAddress,
                'shipping_city' => $shippingCity,
                'shipping_country' => $shippingCountry,
                'billing_address' => $billingAddress,
                'billing_city' => $billingCity,
                'billing_country' => $billingCountry,
                'customer_phone' => $request->customer_phone ?? $profile->phone ?? null,
            ]);

            // Set default shipping method
            if (!$request->has('shipping_method')) {
                $request->merge(['shipping_method' => 'standard']);
            }

            $result = $this->checkoutService->processCheckout($request->all());

            return $this->successResponse($result, "Order placed successfully");

        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), "Validation failed");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
