<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Product;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VendorCouponController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware(['auth:sanctum', 'role:vendor']);
    }

    /**
     * List vendor's coupons
     */
    public function index(Request $request)
    {
        $vendorId = Auth::user()->vendor->id;

        $query = Coupon::where('vendor_id', $vendorId)
            ->orWhere(function ($q) use ($vendorId) {
                $q->where('created_by', 'admin')
                  ->whereJsonContains('eligible_items', $vendorId);
            });

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                  ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        $coupons = $query->withCount('usage')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginationResponse($coupons, 'Coupons retrieved successfully');
    }

    /**
     * Create vendor coupon
     */
    public function store(Request $request)
    {
        $vendorId = Auth::user()->vendor->id;

        $validated = $request->validate([
            'code' => 'nullable|string|max:50|unique:coupons,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percentage,fixed,bogo,free_shipping',
            'discount_value' => 'required|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'applies_to' => 'required|in:all,specific_products,specific_categories',
            'eligible_items' => 'nullable|array',
            'eligible_items.*' => 'integer',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'minimum_quantity' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:0|max:10',
            'starts_at' => 'nullable|date|after_or_equal:today',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_first_time_only' => 'boolean',
            'is_public' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        // ✅ Validate that products/categories belong to this vendor
        if ($validated['applies_to'] === 'specific_products' && !empty($validated['eligible_items'])) {
            $vendorProducts = Product::where('vendor_id', $vendorId)
                ->whereIn('id', $validated['eligible_items'])
                ->pluck('id')
                ->toArray();

            if (count($vendorProducts) !== count($validated['eligible_items'])) {
                return $this->errorResponse('Some products do not belong to your store', 422);
            }
        }

        if ($validated['applies_to'] === 'specific_categories' && !empty($validated['eligible_items'])) {
            // Check if vendor has products in these categories
            $hasProducts = Product::where('vendor_id', $vendorId)
                ->whereIn('category_id', $validated['eligible_items'])
                ->exists();

            if (!$hasProducts) {
                return $this->errorResponse('You have no products in the selected categories', 422);
            }
        }

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Coupon::generateUniqueCode();
        }

        $validated['created_by'] = 'vendor';
        $validated['created_by_id'] = Auth::id();
        $validated['vendor_id'] = $vendorId;
        $validated['is_active'] = true;
        $validated['used_count'] = 0;

        $coupon = Coupon::create($validated);

        return $this->successResponse($coupon->loadCount('usage'), 'Coupon created successfully', 201);
    }

    /**
     * Show single coupon
     */
    public function show($id)
    {
        $vendorId = Auth::user()->vendor->id;

        $coupon = Coupon::where('vendor_id', $vendorId)
            ->orWhere('created_by_id', Auth::id())
            ->withCount('usage')
            ->find($id);

        if (!$coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        return $this->successResponse($coupon, 'Coupon retrieved successfully');
    }

    /**
     * Update vendor coupon
     */
    public function update(Request $request, $id)
    {
        $vendorId = Auth::user()->vendor->id;

        $coupon = Coupon::where('vendor_id', $vendorId)->find($id);
        if (!$coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        $validated = $request->validate([
            'code' => 'sometimes|string|max:50|unique:coupons,code,' . $coupon->id,
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'sometimes|in:percentage,fixed,bogo,free_shipping',
            'discount_value' => 'sometimes|numeric|min:0|max:100',
            'max_discount_amount' => 'nullable|numeric|min:0',
            'applies_to' => 'sometimes|in:all,specific_products,specific_categories',
            'eligible_items' => 'nullable|array',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'minimum_quantity' => 'nullable|integer|min:0',
            'usage_limit' => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:0|max:10',
            'starts_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:starts_at',
            'is_first_time_only' => 'boolean',
            'is_public' => 'boolean',
            'is_active' => 'boolean',
            'metadata' => 'nullable|array',
        ]);

        // ✅ Validate product ownership
        if (isset($validated['applies_to']) && $validated['applies_to'] === 'specific_products') {
            $vendorProducts = Product::where('vendor_id', $vendorId)
                ->whereIn('id', $validated['eligible_items'] ?? [])
                ->pluck('id')
                ->toArray();

            if (count($vendorProducts) !== count($validated['eligible_items'] ?? [])) {
                return $this->errorResponse('Some products do not belong to your store', 422);
            }
        }

        $coupon->update($validated);

        return $this->successResponse($coupon->loadCount('usage'), 'Coupon updated successfully');
    }

    /**
     * Delete coupon
     */
    public function destroy($id)
    {
        $vendorId = Auth::user()->vendor->id;

        $coupon = Coupon::where('vendor_id', $vendorId)->find($id);
        if (!$coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        if ($coupon->usage()->count() > 0) {
            return $this->errorResponse('Cannot delete coupon that has been used', 422);
        }

        $coupon->delete();

        return $this->successResponse(null, 'Coupon deleted successfully');
    }

    /**
     * Toggle coupon status
     */
    public function toggleStatus($id)
    {
        $vendorId = Auth::user()->vendor->id;

        $coupon = Coupon::where('vendor_id', $vendorId)->find($id);
        if (!$coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        $coupon->is_active = !$coupon->is_active;
        $coupon->save();

        return $this->successResponse($coupon, 'Coupon status updated successfully');
    }

    /**
     * Get vendor coupon analytics
     */
    public function analytics(Request $request)
    {
        $vendorId = Auth::user()->vendor->id;

        $couponIds = Coupon::where('vendor_id', $vendorId)->pluck('id');

        $query = \App\Models\CouponUsage::whereIn('coupon_id', $couponIds)
            ->notReversed();

        if ($request->has('date_from')) {
            $query->where('used_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->where('used_at', '<=', $request->date_to);
        }

        $analytics = [
            'total_uses' => $query->count(),
            'total_discount' => $query->sum('discount_amount'),
            'average_discount' => $query->avg('discount_amount') ?? 0,
            'coupons' => Coupon::where('vendor_id', $vendorId)
                ->withCount('usage')
                ->get(),
            'usage_by_date' => $query->select(
                DB::raw('DATE(used_at) as date'),
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get(),
        ];

        return $this->successResponse($analytics, 'Analytics retrieved successfully');
    }
}
