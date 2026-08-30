<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Models\CouponUsage;

class CouponController extends Controller
{
    use ApiResponseTrait;

    protected $couponService;

    public function __construct(CouponService $couponService)
    {
        $this->couponService = $couponService;
        $this->middleware('auth:sanctum');
    }

    public function available(Request $request)
    {
        $coupons = Coupon::available()
            ->public()
            ->where(function ($q) use ($request) {
                $q->whereNull('vendor_id')
                  ->orWhere('vendor_id', $request->user()->vendor_id ?? 0);
            })
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return $this->successResponse($coupons, 'Available coupons retrieved');
    }

    public function validateCoupon(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $cart = $user->cart()->first();

        if (!$cart) {
            return $this->errorResponse('No cart found', 404);
        }

        $coupon = Coupon::where('code', $request->code)->first();

        if (!$coupon) {
            return $this->errorResponse('Invalid coupon code', 422);
        }

        if (!$coupon->is_available) {
            $message = 'Coupon is not available';
            if ($coupon->is_expired) {
                $message = 'This coupon has expired';
            } elseif (!$coupon->is_active) {
                $message = 'This coupon is inactive';
            } elseif ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
                $message = 'This coupon has reached its usage limit';
            }
            return $this->errorResponse($message, 422);
        }

        if (!$coupon->isEligibleForUser($user->id)) {
            return $this->errorResponse('You are not eligible for this coupon', 422);
        }

        if (!$coupon->isEligibleForCart($cart)) {
            return $this->errorResponse('Coupon conditions not met', 422);
        }

        $discount = $coupon->calculateDiscount($cart);

        return $this->successResponse([
            'valid' => true,
            'coupon' => [
                'id' => $coupon->id,
                'code' => $coupon->code,
                'name' => $coupon->name,
                'description' => $coupon->description,
                'discount_type' => $coupon->discount_type,
                'discount_value' => $coupon->discount_value,
                'discount_label' => $coupon->discount_label,
                'discount_amount' => $discount['discount_amount'],
                'eligible_subtotal' => $discount['eligible_subtotal'],
                'discounted_total' => $discount['discounted_total'],
            ],
        ], 'Coupon is valid');
    }

    public function apply(Request $request)
    {
        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $user = $request->user();
        $cart = $user->cart()->first();

        if (!$cart) {
            return $this->errorResponse('No active cart found', 404);
        }

        $result = $this->couponService->applyCoupon($cart, $request->code, $user->id);

        if (!$result['valid']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result, 'Coupon applied successfully');
    }

    public function remove(Request $request)
    {
        $request->validate([
            'code' => 'nullable|string|max:50',
        ]);

        $user = $request->user();
        $cart = $user->cart()->first();

        if (!$cart) {
            return $this->errorResponse('No cart found', 404);
        }

        $result = $this->couponService->removeCoupon($cart, $user->id, $request->code);

        if (!$result['success']) {
            return $this->errorResponse($result['message'], 422);
        }

        return $this->successResponse($result, 'Coupon removed successfully');
    }

    public function applied(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart()->first();

        if (!$cart) {
            return $this->successResponse([], 'No coupons applied');
        }

        $coupons = $this->couponService->getAppliedCoupons($cart);

        return $this->successResponse($coupons, 'Applied coupons retrieved');
    }

    public function clear(Request $request)
    {
        $user = $request->user();
        $cart = $user->cart()->first();

        if (!$cart) {
            return $this->errorResponse('No cart found', 404);
        }

        $result = $this->couponService->clearCoupons($cart);

        return $this->successResponse($result, 'All coupons cleared');
    }

    public function history(Request $request)
    {
        $usage = CouponUsage::where('user_id', $request->user()->id)
            ->with(['coupon', 'order'])
            ->notReversed()
            ->orderBy('used_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginationResponse($usage, 'Coupon history retrieved');
    }
}
