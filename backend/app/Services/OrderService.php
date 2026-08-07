<?php
// app/Services/OrderService.php

namespace App\Services;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderService {
    protected $inventoryService;
    protected $paymentService;

    public function __construct() {
        // You can inject other services here
    }

    public function createOrder(array $data): Order {
        return DB::transaction(function () use ($data) {
            // Validate and prepare items
            $items    = $this->prepareOrderItems($data['items']);
            $totals   = $this->calculateTotals($items, $data);
            $vendorId = $items[0]['vendor_id'] ?? null;

            $couponIds      = [];
            $couponDiscount = 0;

            // ✅ Check if multiple coupons are applied
            if (! empty($data['coupon_codes']) && is_array($data['coupon_codes'])) {
                foreach ($data['coupon_codes'] as $code) {
                    $coupon = Coupon::where('code', $code)->first();
                    if ($coupon && $coupon->is_available) {
                        $cart = $this->createCartFromItems($items);
                        if ($coupon->isEligibleForUser(Auth::user()->id) && $coupon->isEligibleForCart($cart)) {
                            $discountResult  = $coupon->calculateDiscount($cart);
                            $couponDiscount += $discountResult['discount_amount'];
                            $couponIds[]     = $coupon->id;
                        }
                    }
                }
            }
            // ✅ Fallback: single coupon
            elseif (! empty($data['coupon_code'])) {
                $coupon = Coupon::where('code', $data['coupon_code'])->first();
                if ($coupon && $coupon->is_available) {
                    $cart = $this->createCartFromItems($items);
                    if ($coupon->isEligibleForUser(Auth::user()->id) && $coupon->isEligibleForCart($cart)) {
                        $discountResult = $coupon->calculateDiscount($cart);
                        $couponDiscount = $discountResult['discount_amount'];
                        $couponIds[]    = $coupon->id;
                    }
                }
            }

            // Update totals
            $totals['discount_total'] += $couponDiscount;
            $totals['grand_total']    = $totals['subtotal'] - $totals['discount_total'] + $totals['tax_total'] + $totals['shipping_total'];

            // Create order
            $order  = Order::create([
                'user_id'            => Auth::id(),
                'vendor_id'          => $vendorId,
                'status'             => 'pending',
                'payment_status'     => 'pending',
                'fulfillment_status' => 'unfulfilled',
                'subtotal'           => $totals['subtotal'],
                'discount_total'     => $totals['discount_total'],
                'coupon_id'          => $couponIds[0] ?? null,
                'coupon_discount'    => $couponDiscount,
                'tax_total'          => $totals['tax_total'],
                'shipping_total'     => $totals['shipping_total'],
                'grand_total'        => $totals['grand_total'],
                // ... other fields
            ]);

            // Create order items
            foreach ($items as $item) {
                $this->createOrderItem($order, $item);
                $this->updateStock($item);
            }

            // ✅ Record usage for EACH coupon
            if (! empty($couponIds) && $couponDiscount > 0) {
                $discountPerCoupon = $couponDiscount / count($couponIds);
                foreach ($couponIds as $couponId) {
                    CouponUsage::create([
                        'coupon_id'         => $couponId,
                        'user_id'           => Auth::id(),
                        'order_id'          => $order->id,
                        'original_subtotal' => $totals['subtotal'],
                        'discount_amount'   => $discountPerCoupon,
                        'discounted_total'  => $totals['grand_total'],
                        'source'            => 'checkout',
                        'used_at'           => now(),
                    ]);

                    // ✅ Increment usage count for each coupon
                    $coupon = Coupon::find($couponId);
                    if ($coupon) {
                        $coupon->increment('used_count');
                    }
                }
            }

            return $order->load('items');
        });
    }

    protected function createCartFromItems(array $items) {
        $cart                 = new \stdClass();
        $cart->items          = [];
        $cart->subtotal       = 0;
        $cart->total_quantity = 0;
        $cart->shipping_cost  = 0;

        foreach ($items as $item) {
            $cartItem                       = new \stdClass();
            $cartItem->product_id           = $item['product_id'];
            $cartItem->product              = new \stdClass();
            $cartItem->product->category_id = $item['category_id'] ?? null;
            $cartItem->quantity             = $item['quantity'];
            $cartItem->unit_price           = $item['unit_price'];
            $cartItem->total                = $item['subtotal'];

            $cart->items[]         = $cartItem;
            $cart->subtotal       += $item['subtotal'];
            $cart->total_quantity += $item['quantity'];
        }

        return $cart;
    }
    protected function prepareOrderItems(array $items): array {
        $preparedItems = [];

        foreach ($items as $item) {
            try {
                $product = Product::find($item['product_id']);
                if (! $product) {
                    throw new \Exception("One of the products in your order is no longer available. Please refresh your cart.");
                }

                $variation = null;
                if (! empty($item['product_variation_id'])) {
                    $variation = ProductVariation::where('id', $item['product_variation_id'])
                        ->where('product_id', $product->id)
                        ->first();

                    if (! $variation) {
                        throw new \Exception("The selected variation is not available for '{$product->name}'.");
                    }
                }

                // Check stock availability
                $stockQuantity = $variation ? $variation->stock_quantity : $product->stock_quantity;
                if ($stockQuantity < $item['quantity']) {
                    $productName = $variation ? "{$product->name} - {$variation->name}" : $product->name;
                    throw new \Exception("We only have {$stockQuantity} units available for '{$productName}'.");
                }

                $unitPrice = $variation ? $variation->price : $product->price;
                $subtotal  = $unitPrice * $item['quantity'];

                $preparedItems[] = [
                    'product_id'             => $product->id,
                    'product_variation_id'   => $variation?->id,
                    'vendor_id'              => $product->vendor_id,
                    'product_name'           => $product->name,
                    'product_sku'            => $variation ? $variation->sku : $product->sku,
                    'product_variation_name' => $variation?->name,
                    'product_attributes'     => $variation?->attributes,
                    'quantity'               => $item['quantity'],
                    'unit_price'             => $unitPrice,
                    'subtotal'               => $subtotal,
                    'discount'               => 0,
                    'tax'                    => $this->calculateTax($unitPrice, $item['quantity'], $product->tax_rate),
                    'total'                  => $subtotal,
                ];

            } catch (\Exception $e) {
                throw $e;
            }
        }

        return $preparedItems;
    }

    protected function calculateTotals(array $items, array $data): array {
        $subtotal      = array_sum(array_column($items, 'subtotal'));
        $discountTotal = array_sum(array_column($items, 'discount'));
        $taxTotal      = array_sum(array_column($items, 'tax'));
        $shippingTotal = $data['shipping_total'] ?? $this->calculateShipping($subtotal, $data['shipping_method'] ?? 'standard');
        $grandTotal    = $subtotal - $discountTotal + $taxTotal + $shippingTotal;

        return [
            'subtotal'       => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total'      => $taxTotal,
            'shipping_total' => $shippingTotal,
            'grand_total'    => $grandTotal,
        ];
    }

    protected function calculateShipping($subtotal, $method): float {
        if ($subtotal > 100) {
            return 0;
        }

        return match ($method) {
            'express'   => 15.00,
            'overnight' => 25.00,
            default     => 5.99,
        };
    }

    protected function createOrderItem(Order $order, array $item): OrderItem {
        return OrderItem::create([
            'order_id'               => $order->id,
            'product_id'             => $item['product_id'],
            'product_variation_id'   => $item['product_variation_id'],
            'product_name'           => $item['product_name'],
            'product_sku'            => $item['product_sku'],
            'product_variation_name' => $item['product_variation_name'],
            'product_attributes'     => $item['product_attributes'],
            'unit_price'             => $item['unit_price'],
            'quantity'               => $item['quantity'],
            'subtotal'               => $item['subtotal'],
            'discount'               => $item['discount'],
            'tax'                    => $item['tax'],
            'total'                  => $item['total'],
            'status'                 => 'pending',
        ]);
    }

    protected function updateStock(array $item): void {
        if ($item['product_variation_id']) {
            $variation = ProductVariation::find($item['product_variation_id']);
            if ($variation) {
                $variation->decrement('stock_quantity', $item['quantity']);
            }
        } else {
            $product = Product::find($item['product_id']);
            if ($product) {
                $product->decrement('stock_quantity', $item['quantity']);
            }
        }
    }

    protected function calculateTax(float $price, int $quantity, float $taxRate): float {
        return round(($price * $quantity * $taxRate) / 100, 2);
    }

    public function updateOrderStatus(Order $order, array $data): Order {
        return DB::transaction(function () use ($order, $data) {
            $oldStatus = $order->status;

            $order->update($data);

            // Handle status-specific actions
            if (isset($data['status']) && $data['status'] === 'cancelled' && $oldStatus !== 'cancelled') {
                $this->handleOrderCancellation($order);
            }

            if (isset($data['status']) && $data['status'] === 'shipped') {
                $order->update(['shipped_at' => now()]);
            }

            if (isset($data['status']) && $data['status'] === 'delivered') {
                $order->update(['delivered_at' => now()]);
            }

            if (isset($data['payment_status']) && $data['payment_status'] === 'paid' && ! $order->paid_at) {
                $order->update(['paid_at' => now()]);
            }

            return $order->fresh();
        });
    }

    protected function handleOrderCancellation(Order $order): void {
        // Restore stock for cancelled order
        foreach ($order->items as $item) {
            if ($item->product_variation_id) {
                $variation = ProductVariation::find($item->product_variation_id);
                if ($variation) {
                    $variation->increment('stock_quantity', $item->quantity);
                }
            } else {
                $product = Product::find($item->product_id);
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                }
            }
        }
    }

    public function getUserOrders(int $userId, array $filters = []) {
        $query = Order::byUser($userId)->with('items');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['from_date'])) {
            $query->whereDate('created_at', '>=', $filters['from_date']);
        }

        if (! empty($filters['to_date'])) {
            $query->whereDate('created_at', '<=', $filters['to_date']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }

    public function getVendorOrders(int $vendorId, array $filters = []) {
        $query = Order::byVendor($vendorId)->with('items');

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['payment_status'])) {
            $query->where('payment_status', $filters['payment_status']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($filters['per_page'] ?? 15);
    }
}
