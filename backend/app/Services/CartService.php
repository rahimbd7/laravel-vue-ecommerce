<?php
// app/Services/CartService.php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;
use Laravel\Sanctum\PersonalAccessToken;

class CartService {
    /**
     * Get or create cart for current user/session with persistent cookie
     */

    protected function getAuthenticatedUser() {
        $token = request()->bearerToken();
        if (! $token) {
            return null;
        }

        $accessToken = PersonalAccessToken::findToken($token);
        return $accessToken && $accessToken->tokenable ? $accessToken->tokenable : null;
    }
    public function getCart() {
        $user = $this->getAuthenticatedUser();
        if ($user) {
            // Logged in user - get or create cart with user_id
            $cart = Cart::firstOrCreate(
                ['user_id' => $user->id, 'status' => 'active']
            );

            // Merge guest cart if exists
            $this->mergeGuestCart($cart);

        } else {
            // Guest user - use persistent guest token from cookie
            $guestToken = $this->getGuestToken();

            // Check if cart already exists with this token
            $cart = Cart::where('guest_token', $guestToken)
                ->where('status', 'active')
                ->first();

            // If no cart found, create new one
            if (! $cart) {
                $cart = Cart::create([
                    'guest_token' => $guestToken,
                    'status'      => 'active',
                    'expires_at'  => now()->addDays(30),
                    'item_count'  => 0,
                    'subtotal'    => 0,
                    'grand_total' => 0,
                ]);
            }
        }

        return $cart->load('items.product', 'items.variation');
    }

    /**
     * Get or create persistent guest token (stored in cookie for 30 days)
     */
    protected function getGuestToken() {
        // Try to get token from various sources
        $token = null;

        // 1. Check request cookie (sent by browser)
        $token = request()->cookie('guest_cart_token');

        // 2. If not found, check Cookie facade
        if (! $token) {
            $token = Cookie::get('guest_cart_token');
        }

        // 3. If still not found, check for existing cart without user
        if (! $token) {
            // Look for the most recent guest cart
            $latestCart = Cart::whereNull('user_id')
                ->where('status', 'active')
                ->orderBy('updated_at', 'desc')
                ->first();

            if ($latestCart && $latestCart->guest_token) {
                $token = $latestCart->guest_token;
                // Restore the cookie
                $this->setGuestTokenCookie($token);
            }
        }

        // 4. Finally, generate new token if none exists
        if (! $token) {
            $token = 'guest_' . Str::random(60);
            $this->setGuestTokenCookie($token);
        }

        return $token;
    }

    /**
     * Set guest token cookie (called in middleware or controller)
     */
    public function setGuestTokenCookie($token) {
        // Queue cookie for 30 days
        Cookie::queue('guest_cart_token', $token, 60 * 24 * 30);

        // Also set in request for immediate use
        request()->cookies->set('guest_cart_token', $token);
    }

    /**
     * Merge guest cart into user cart after login
     */
    protected function mergeGuestCart(Cart $userCart) {
        $guestToken = $this->getGuestToken();

        if (! $guestToken) {
            return;
        }

        $guestCart = Cart::where('guest_token', $guestToken)
            ->where('status', 'active')
            ->whereNull('user_id')
            ->first();

        if ($guestCart && $guestCart->id !== $userCart->id) {
            // Merge items from guest cart to user cart
            foreach ($guestCart->items as $guestItem) {
                $existingItem = $userCart->items()
                    ->where('product_id', $guestItem->product_id)
                    ->where('product_variation_id', $guestItem->product_variation_id)
                    ->first();

                if ($existingItem) {
                    // Combine quantities
                    $existingItem->quantity += $guestItem->quantity;
                    $existingItem->subtotal  = $existingItem->unit_price * $existingItem->quantity;
                    $existingItem->tax       = ($existingItem->subtotal * ($existingItem->product->tax_rate ?? 0)) / 100;
                    $existingItem->total     = $existingItem->subtotal + $existingItem->tax;
                    $existingItem->save();
                } else {
                    // Move item to user cart
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }

            // Delete guest cart
            $guestCart->delete();

            // Refresh totals
            $userCart->refreshTotals();
        }
    }

    /**
     * Add item to cart
     */
    public function addItem($productId, $variationId, $quantity) {
        $cart = $this->getCart();

        // Make sure cart is saved before adding items
        if (! $cart->exists) {
            $cart->save();
        }

        $cart->addItem($productId, $variationId, $quantity);

        return $cart->fresh('items');
    }

    /**
     * Remove item from cart
     */
    public function removeItem($cartItemId) {
        $cart = $this->getCart();
        $cart->removeItem($cartItemId);

        return $cart->fresh('items');
    }

    /**
     * Update item quantity
     */
    public function updateQuantity($cartItemId, $quantity) {
        $cart = $this->getCart();
        $cart->updateQuantity($cartItemId, $quantity);

        return $cart->fresh('items');
    }

    /**
     * Clear cart
     */
    public function clearCart() {
        $cart = $this->getCart();
        $cart->clear();

        return $cart->fresh('items');
    }

    /**
     * Get cart summary
     */
    public function getSummary() {
        $cart = $this->getCart();

        return [
            'cart_id'         => $cart->id,
            'item_count'      => $cart->item_count,
            'subtotal'        => number_format($cart->subtotal, 2),
            'tax_total'       => number_format($cart->tax_total, 2),
            'shipping_total'  => number_format($cart->shipping_total, 2),
            'discount_total'  => number_format($cart->discount_total, 2),
            'grand_total'     => number_format($cart->grand_total, 2),
            'coupon_code'     => $cart->coupon_code,
            'coupon_discount' => number_format($cart->coupon_discount, 2),
        ];
    }

    /**
     * Move item to saved for later
     */
    public function saveForLater($cartItemId) {
        $cartItem = CartItem::findOrFail($cartItemId);
        $cartItem->saveForLater();

        $cart = $this->getCart();
        return $cart->fresh('items');
    }

    /**
     * Move item back to cart
     */
    public function moveToCart($cartItemId) {
        $cartItem = CartItem::findOrFail($cartItemId);
        $cartItem->moveToCart();

        $cart = $this->getCart();
        return $cart->fresh('items');
    }

    /**
     * Sync guest cart with user cart (manual sync)
     */
    public function syncGuestCart($guestToken = null) {
        $user = Request::user();

        if (! $user) {
            throw new \Exception("User must be logged in to sync cart");
        }

        $token = $guestToken ?? Cookie::get('guest_cart_token');

        if (! $token) {
            return $this->getCart();
        }

        $guestCart = Cart::where('guest_token', $token)
            ->where('status', 'active')
            ->whereNull('user_id')
            ->first();

        if (! $guestCart) {
            return $this->getCart();
        }

        // Get or create user cart
        $userCart = Cart::firstOrCreate(
            ['user_id' => $user->id, 'status' => 'active']
        );

        // Merge items
        foreach ($guestCart->items as $guestItem) {
            $existingItem = $userCart->items()
                ->where('product_id', $guestItem->product_id)
                ->where('product_variation_id', $guestItem->product_variation_id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $guestItem->quantity;
                $existingItem->save();
            } else {
                $guestItem->update(['cart_id' => $userCart->id]);
            }
        }

        // Delete guest cart
        $guestCart->delete();

        // Refresh totals
        $userCart->refreshTotals();

        return $userCart->load('items.product', 'items.variation');
    }

    /**
     * Get cart count (number of items)
     */
    public function getCartCount() {
        $cart = $this->getCart();
        return $cart->item_count;
    }

    /**
     * Get cart total
     */
    public function getCartTotal() {
        $cart = $this->getCart();
        return $cart->grand_total;
    }
}