<?php

namespace App\Http\Controllers\API\Cart;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    use ApiResponseTrait;

    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Get cart contents (Guest or Authenticated)
     */
    public function index()
    {
        try {
            $cart = $this->cartService->getCart();
            return $this->successResponse($cart, "Cart retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Get cart summary (Guest or Authenticated)
     */
    public function summary()
    {
        try {
            $summary = $this->cartService->getSummary();
            return $this->successResponse($summary, "Cart summary retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Add item to cart (Guest or Authenticated)
     */
    public function addItem(Request $request)
    {
        try {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'product_variation_id' => 'nullable|exists:product_variations,id',
                'quantity' => 'required|integer|min:1|max:100'
            ]);

            $cart = $this->cartService->addItem(
                $request->product_id,
                $request->product_variation_id,
                $request->quantity
            );

            return $this->successResponse($cart, "Item added to cart successfully");

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), "Validation failed");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Update cart item quantity (Guest or Authenticated)
     */
    public function updateItem(Request $request, $cartItemId)
    {
        try {
            $request->validate([
                'quantity' => 'required|integer|min:1|max:100'
            ]);

            $cart = $this->cartService->updateQuantity($cartItemId, $request->quantity);

            return $this->successResponse($cart, "Cart updated successfully");

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), "Validation failed");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Remove item from cart (Guest or Authenticated)
     */
    public function removeItem($cartItemId)
    {
        try {
            $cart = $this->cartService->removeItem($cartItemId);
            return $this->successResponse($cart, "Item removed from cart");

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Clear entire cart (Guest or Authenticated)
     */
    public function clear()
    {
        try {
            $cart = $this->cartService->clearCart();
            return $this->successResponse($cart, "Cart cleared successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Save item for later (Guest or Authenticated)
     */
    public function saveForLater($cartItemId)
    {
        try {
            $cart = $this->cartService->saveForLater($cartItemId);
            return $this->successResponse($cart, "Item saved for later");

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Move item back to cart (Guest or Authenticated)
     */
    public function moveToCart($cartItemId)
    {
        try {
            $cart = $this->cartService->moveToCart($cartItemId);
            return $this->successResponse($cart, "Item moved to cart");

        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    /**
     * Sync guest cart with user cart after login (Authenticated only)
     */
    public function syncGuestCart(Request $request)
    {
        try {
            $request->validate([
                'guest_token' => 'nullable|string'
            ]);

            $cart = $this->cartService->syncGuestCart($request->guest_token);
            return $this->successResponse($cart, "Cart synced successfully");

        } catch (ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), "Validation failed");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}