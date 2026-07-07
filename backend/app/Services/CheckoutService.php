<?php
// app/Services/CheckoutService.php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutService {
    protected $cartService;
    protected $paymentService;

    public function __construct(CartService $cartService, PaymentService $paymentService) {
        $this->cartService    = $cartService;
        $this->paymentService = $paymentService;
    }

    /**
     * Process checkout - Converts cart to order
     */
    public function processCheckout(array $data) {
        return DB::transaction(function () use ($data) {
            $user    = Auth::user();
            $cart    = $this->cartService->getCart();
            $profile = $user->profile;

            // ✅ Updated: Include state and postal_code in hasAddress check
            $hasAddress = $profile &&
            ! empty($profile->address) &&
            ! empty($profile->city) &&
            ! empty($profile->state) &&
            ! empty($profile->postal_code) &&
            ! empty($profile->country);

            // ✅ Updated: Get ALL address fields from profile including state and postal_code
            $shippingAddress    = $data['shipping_address'] ?? ($profile?->address ?? null);
            $shippingCity       = $data['shipping_city'] ?? ($profile?->city ?? null);
            $shippingState      = $data['shipping_state'] ?? ($profile?->state ?? null);
            $shippingPostalCode = $data['shipping_postal_code'] ?? ($profile?->postal_code ?? null);
            $shippingCountry    = $data['shipping_country'] ?? ($profile?->country ?? null);

            $useSameAddress = $data['use_same_address'] ?? false;

            if ($useSameAddress) {
                $billingAddress    = $shippingAddress;
                $billingCity       = $shippingCity;
                $billingState      = $shippingState;
                $billingPostalCode = $shippingPostalCode;
                $billingCountry    = $shippingCountry;
            } else {
                $billingAddress    = $data['billing_address'] ?? ($profile?->address ?? null);
                $billingCity       = $data['billing_city'] ?? ($profile?->city ?? null);
                $billingState      = $data['billing_state'] ?? ($profile?->state ?? null);
                $billingPostalCode = $data['billing_postal_code'] ?? ($profile?->postal_code ?? null);
                $billingCountry    = $data['billing_country'] ?? ($profile?->country ?? null);
            }

            // ✅ Updated: Validate all address fields
            if (empty($shippingAddress) || empty($shippingCity) || empty($shippingState) || empty($shippingPostalCode) || empty($shippingCountry)) {
                throw new \Exception('Complete shipping address (address, city, state, postal code, country) is required.');
            }

            if (empty($billingAddress) || empty($billingCity) || empty($billingState) || empty($billingPostalCode) || empty($billingCountry)) {
                throw new \Exception('Complete billing address is required.');
            }

            // ✅ Updated: Save ALL address fields to profile including state and postal_code
            $shouldSaveAddress = false;

            if (! $hasAddress) {
                $shouldSaveAddress = true;
            } elseif ($data['save_address'] ?? false) {
                $shouldSaveAddress = true;
            }

            if ($shouldSaveAddress && ! empty($shippingAddress)) {
                $profileData = [
                    'address'     => $shippingAddress,
                    'city'        => $shippingCity,
                    'state'       => $shippingState,      // ✅ ADDED
                    'postal_code' => $shippingPostalCode, // ✅ ADDED
                    'country'     => $shippingCountry,
                    'phone'       => $data['customer_phone'] ?? $profile?->phone ?? null,
                ];

                if ($profile) {
                    $profile->update($profileData);
                } else {
                    $user->profile()->create([
                        'user_uuid' => $user->uuid,
                        ...$profileData,
                    ]);
                }
            }

            // ✅ Updated: Prepare ALL address fields for order creation
            $data['shipping_address']     = $shippingAddress;
            $data['shipping_city']        = $shippingCity;
            $data['shipping_state']       = $shippingState;
            $data['shipping_postal_code'] = $shippingPostalCode;
            $data['shipping_country']     = $shippingCountry;
            $data['billing_address']      = $billingAddress;
            $data['billing_city']         = $billingCity;
            $data['billing_state']        = $billingState;
            $data['billing_postal_code']  = $billingPostalCode;
            $data['billing_country']      = $billingCountry;

            // Validate cart is not empty
            if ($cart->isEmpty()) {
                throw new \Exception('Your cart is empty.');
            }

            // Calculate totals
            $totals = $this->calculateTotals($cart, $data);

            // Create order from cart
            $order = $this->createOrder($data, $user, $cart, $totals);
            //create payment record
            $payment = $this->paymentService->createPayment($order, [
                'payment_method' => $data['payment_method'] ?? 'cod',
            ]);

            // ✅ If payment is COD, mark as success immediately
            if ($data['payment_method'] === 'cod') {
                $this->paymentService->confirmPayment($payment);
            }

            // Create order items from cart items
            $this->createOrderItems($order, $cart);

            // Clear cart items (keep cart record)
            $cart->clear();

            return [
                'order'        => $order->load('items'),
                'order_number' => $order->order_number,
                'grand_total'  => (float) $order->grand_total,
                'message'      => 'Order placed successfully',
            ];
        });
    }

    /**
     * Calculate order totals
     */
    protected function calculateTotals($cart, array $data): array {
        $subtotal     = $cart->subtotal;
        $shippingCost = $this->calculateShippingCost($subtotal, $data['shipping_method'] ?? 'standard');
        $taxRate      = 10;
        $tax          = ($subtotal * $taxRate) / 100;
        $grandTotal   = $subtotal + $tax + $shippingCost;

        return [
            'subtotal'      => (float) $subtotal,
            'shipping_cost' => (float) $shippingCost,
            'tax'           => (float) $tax,
            'grand_total'   => (float) $grandTotal,
        ];
    }

    /**
     * Calculate shipping cost
     */
    protected function calculateShippingCost($subtotal, $method): float {
        if ($subtotal >= 100) {
            return 0;
        }

        return match ($method) {
            'express'   => 15.00,
            'overnight' => 25.00,
            default     => 5.99,
        };
    }

    /**
     * Create order record
     */
    protected function createOrder(array $data, $user, $cart, array $totals): Order {
        return Order::create([
            'user_id'              => $user->id,
            'vendor_id'            => $this->getVendorIdFromCart($cart),
            'status'               => 'pending',
            'payment_status'       => 'pending',
            'fulfillment_status'   => 'unfulfilled',
            'subtotal'             => $totals['subtotal'],
            'discount_total'       => 0,
            'tax_total'            => $totals['tax'],
            'shipping_total'       => $totals['shipping_cost'],
            'grand_total'          => $totals['grand_total'],
            'payment_method'       => $data['payment_method'],
            'shipping_method'      => $data['shipping_method'] ?? 'standard',
            'customer_name'        => $user->name,
            'customer_email'       => $user->email,
            'customer_phone'       => $data['customer_phone'] ?? $user->profile->phone ?? null,
            'billing_address'      => $data['billing_address'],
            'billing_city'         => $data['billing_city'],
            'billing_state'        => $data['billing_state'] ?? null,
            'billing_postal_code'  => $data['billing_postal_code'] ?? null,
            'billing_country'      => $data['billing_country'],
            'shipping_address'     => $data['shipping_address'],
            'shipping_city'        => $data['shipping_city'],
            'shipping_state'       => $data['shipping_state'] ?? null,
            'shipping_postal_code' => $data['shipping_postal_code'] ?? null,
            'shipping_country'     => $data['shipping_country'],
            'customer_notes'       => $data['notes'] ?? null,
            'metadata'             => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }

    /**
     * Create order items from cart items
     */
    protected function createOrderItems(Order $order, $cart): void {
        foreach ($cart->items as $cartItem) {
            OrderItem::create([
                'order_id'               => $order->id,
                'product_id'             => $cartItem->product_id,
                'product_variation_id'   => $cartItem->product_variation_id,
                'product_name'           => $cartItem->product_name,
                'product_sku'            => $cartItem->product_sku,
                'product_variation_name' => $cartItem->product_variation_name,
                'product_attributes'     => $cartItem->product_attributes,
                'unit_price'             => $cartItem->unit_price,
                'quantity'               => $cartItem->quantity,
                'subtotal'               => $cartItem->subtotal,
                'discount'               => 0,
                'tax'                    => $cartItem->tax,
                'total'                  => $cartItem->total,
                'status'                 => 'pending',
                'tracking_number'        => null,
            ]);

            // Update product sold count
            if ($cartItem->product) {
                $cartItem->product->increment('sold_count', $cartItem->quantity);
            }
        }
    }

    /**
     * Get vendor ID from cart
     */
    protected function getVendorIdFromCart($cart): ?int {
        $firstItem = $cart->items->first();
        return $firstItem && $firstItem->product ? $firstItem->product->vendor_id : null;
    }

    /**
     * Get checkout summary
     */
    public function getCheckoutSummary() {
        $cart = $this->cartService->getCart();
        $user = Auth::user();

        if ($cart->isEmpty()) {
            throw new \Exception('Your cart is empty.');
        }

        $totals = $this->calculateTotals($cart, ['shipping_method' => 'standard']);

        return [
            'cart_items'       => $cart->items,
            'item_count'       => $cart->item_count,
            'subtotal'         => (float) $cart->subtotal,
            'shipping_cost'    => $totals['shipping_cost'],
            'tax'              => $totals['tax'],
            'grand_total'      => $totals['grand_total'],
            'customer'         => [
                'name'  => $user->name,
                'email' => $user->email,
            ],
            'shipping_methods' => [
                'standard'  => ['name' => 'Standard Shipping', 'price' => 5.99, 'days' => '5-7 business days'],
                'express'   => ['name' => 'Express Shipping', 'price' => 15.00, 'days' => '2-3 business days'],
                'overnight' => ['name' => 'Overnight Shipping', 'price' => 25.00, 'days' => '1 business day'],
            ],
            'payment_methods'  => [
                'cod'           => ['name' => 'Cash on Delivery', 'description' => 'Pay when you receive the order'],
                'bank_transfer' => ['name' => 'Bank Transfer', 'description' => 'Pay via bank transfer'],
                'credit_card'   => ['name' => 'Credit Card', 'description' => 'Pay with credit card'],
                'paypal'        => ['name' => 'PayPal', 'description' => 'Pay with PayPal'],
            ],
        ];
    }
}
