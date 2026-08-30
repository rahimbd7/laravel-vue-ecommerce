<?php
namespace App\Services;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\CouponUsage;

class CouponService {
    /**
     * Validate and apply coupon to cart
     */
    public function applyCoupon(Cart $cart, string $code, int $userId): array {
        $coupon = Coupon::where('code', $code)->first();

        if (! $coupon) {
            return ['valid' => false, 'message' => 'Invalid coupon code'];
        }

        $validation = $this->validateCoupon($coupon, $cart, $userId);
        if (! $validation['valid']) {
            return $validation;
        }

        $discount = $coupon->calculateDiscount($cart);

        $added = $cart->addCoupon($coupon->code, $discount['discount_amount'], $coupon->id);

        if (! $added) {
            return ['valid' => false, 'message' => 'This coupon is already applied'];
        }

        return [
            'valid'           => true,
            'message'         => 'Coupon applied successfully',
            'coupon'          => $coupon,
            'discount'        => $discount,
            'applied_coupons' => $cart->applied_coupons,
            'total_discount'  => $cart->getTotalDiscount(),
            'grand_total'     => $cart->grand_total,
        ];
    }

    /**
     * Validate coupon eligibility
     */
    public function validateCoupon(Coupon $coupon, Cart $cart, int $userId): array {
        if (! $coupon->is_available) {
            return ['valid' => false, 'message' => 'Coupon is not available'];
        }

        if (! $coupon->isEligibleForUser($userId)) {
            return ['valid' => false, 'message' => 'You are not eligible for this coupon'];
        }

        if (! $coupon->isEligibleForCart($cart)) {
            return ['valid' => false, 'message' => 'Coupon conditions not met'];
        }

        // Check per-customer usage limit
        $usageCount = CouponUsage::where('coupon_id', $coupon->id)
            ->where('user_id', $userId)
            ->where('is_reversed', false)
            ->count();

        if ($coupon->usage_limit_per_customer > 0 && $usageCount >= $coupon->usage_limit_per_customer) {
            return ['valid' => false, 'message' => 'You have already used this coupon the maximum number of times'];
        }

        return ['valid' => true, 'message' => 'Coupon is valid'];
    }

    /**
     * Get items that discount was applied to
     */
    private function getAppliedItems(Coupon $coupon, Cart $cart): array {
        $items = [];
        foreach ($cart->items as $item) {
            if ($coupon->applies_to === 'all') {
                $items[] = [
                    'product_id'     => $item->product_id,
                    'product_name'   => $item->product->name,
                    'quantity'       => $item->quantity,
                    'original_price' => $item->unit_price,
                    'total'          => $item->total,
                ];
            } else {
                $eligibleIds = $coupon->eligible_items ?? [];
                if (in_array($item->product_id, $eligibleIds) ||
                    in_array($item->product->category_id, $eligibleIds)) {
                    $items[] = [
                        'product_id'     => $item->product_id,
                        'product_name'   => $item->product->name,
                        'quantity'       => $item->quantity,
                        'original_price' => $item->unit_price,
                        'total'          => $item->total,
                    ];
                }
            }
        }
        return $items;
    }

    /**
     * Remove coupon from cart (reverse usage)
     */
    public function removeCoupon(Cart $cart, int $userId, ?string $couponCode = null): array {
        if (empty($cart->applied_coupons)) {
            return ['success' => false, 'message' => 'No coupon found'];
        }

        if (! $couponCode) {
            $last       = end($cart->applied_coupons);
            $couponCode = $last['code'];
        }

        $removed = $cart->removeCoupon($couponCode);

        if (! $removed) {
            return ['success' => false, 'message' => 'Coupon not found'];
        }

        return [
            'success'         => true,
            'message'         => 'Coupon removed successfully',
            'applied_coupons' => $cart->applied_coupons,
            'total_discount'  => $cart->getTotalDiscount(),
        ];
    }

    /**
     * Get analytics for dashboard
     */
    public function getAnalytics($filters = []) {
        return [
            'coupons'     => Coupon::getAnalytics($filters),
            'usage'       => CouponUsage::getUsageAnalytics($filters),
            'performance' => $this->getPerformanceMetrics($filters),
        ];
    }
    public function getAppliedCoupons(Cart $cart): array {
        return $cart->applied_coupons ?? [];
    }

    public function clearCoupons(Cart $cart): array {
        $cart->clearCoupons();
        return ['success' => true, 'message' => 'All coupons cleared'];
    }
    /**
     * Get performance metrics
     */
    private function getPerformanceMetrics($filters = []) {
        $query = CouponUsage::notReversed();

        if (isset($filters['date_from'])) {
            $query->where('used_at', '>=', $filters['date_from']);
        }
        if (isset($filters['date_to'])) {
            $query->where('used_at', '<=', $filters['date_to']);
        }

        $totalDiscount = $query->sum('discount_amount');
        $totalOrders   = $query->count();

        return [
            'total_discount'   => $totalDiscount,
            'total_orders'     => $totalOrders,
            'average_discount' => $totalOrders > 0 ? $totalDiscount / $totalOrders : 0,
            'conversion_rate'  => $this->calculateConversionRate($filters),
            'revenue_impact'   => $this->calculateRevenueImpact($filters),
        ];
    }

    private function calculateConversionRate($filters): float {
        // Implementation depends on your analytics setup
        return 0.0;
    }

    private function calculateRevenueImpact($filters): array {
        // Implementation depends on your analytics setup
        return [
            'total_revenue'       => 0,
            'discount_percentage' => 0,
        ];
    }
}
