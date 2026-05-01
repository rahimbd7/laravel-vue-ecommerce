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

            /** @var \App\Models\User|null $user */
            $user = auth()->user();
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
        // ✅ Only validation here - business logic delegated to service
        $validated = $request->validate([
            'payment_method' => 'required|in:cod,bank_transfer,credit_card,paypal',
            'shipping_method' => 'nullable|in:standard,express,overnight',
            'notes' => 'nullable|string|max:1000',
            'shipping_address' => 'nullable|string|max:500',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_country' => 'nullable|string|size:2',
            'billing_address' => 'nullable|string|max:500',
            'billing_city' => 'nullable|string|max:100',
            'billing_country' => 'nullable|string|size:2',
            'save_address' => 'boolean',
            'use_same_address' => 'boolean',
        ]);

        $result = $this->checkoutService->processCheckout($validated);

        return $this->successResponse($result, "Order placed successfully");

    } catch (\Illuminate\Validation\ValidationException $e) {
        return $this->validationErrorResponse($e->errors(), "Validation failed");
    } catch (\Exception $e) {
        return $this->errorResponse($e->getMessage(), 400);
    }
}
}
