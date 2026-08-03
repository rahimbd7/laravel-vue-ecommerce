<?php
namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Services\CouponService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminCouponController extends Controller {
    use ApiResponseTrait;

    protected $couponService;

    public function __construct(CouponService $couponService) {
        $this->couponService = $couponService;
        $this->middleware(['auth:sanctum', 'role:admin']);
    }

    /**
     * List all coupons with filters
     */
    public function index(Request $request) {
        $query = Coupon::withCount('usage');

        // Filter by status
        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            } elseif ($request->status === 'exhausted') {
                $query->whereColumn('used_count', '>=', 'usage_limit');
            }
        }

        // Search
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        // Filter by vendor
        if ($request->has('vendor_id')) {
            $query->where('vendor_id', $request->vendor_id);
        }

        // Filter by creator
        if ($request->has('created_by')) {
            $query->where('created_by', $request->created_by);
        }

        $coupons = $query->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginationResponse($coupons, 'Coupons retrieved successfully');
    }

    /**
     * Create new coupon
     */
    public function store(Request $request) {
        $user      = Auth::user();
        $validated = $request->validate([
            'code'                      => 'nullable|string|max:50|unique:coupons,code',
            'name'                      => 'required|string|max:255',
            'description'               => 'nullable|string',
            'discount_type'             => 'required|in:percentage,fixed,bogo,free_shipping',
            'discount_value'            => 'required|numeric|min:0|max:100',
            'max_discount_amount'       => 'nullable|numeric|min:0',
            'applies_to'                => 'required|in:all,specific_products,specific_categories,specific_vendors',
            'eligible_items'            => 'nullable|array',
            'eligible_items.*'          => 'integer',
            'minimum_order_amount'      => 'nullable|numeric|min:0',
            'minimum_quantity'          => 'nullable|integer|min:0',
            'usage_limit'               => 'nullable|integer|min:1',
            'usage_limit_per_customer'  => 'nullable|integer|min:0|max:10',
            'starts_at'                 => 'nullable|date|after_or_equal:today',
            'expires_at'                => 'nullable|date|after:starts_at',
            'is_first_time_only'        => 'boolean',
            'eligible_customer_types'   => 'nullable|array',
            'eligible_customer_types.*' => 'in:customer,vendor,admin',
            'is_public'                 => 'boolean',
            'metadata'                  => 'nullable|array',
        ]);

        // Auto-generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = Coupon::generateUniqueCode();
        }

        $validated['created_by']    = 'admin';
        $validated['created_by_id'] = $user->id;
        $validated['is_active']     = true;
        $validated['used_count']    = 0;

        $coupon = Coupon::create($validated);

        return $this->successResponse($coupon->loadCount('usage'), 'Coupon created successfully', 201);
    }

    /**
     * Show single coupon
     */
    public function show($id) {
        $coupon = Coupon::withCount('usage')->find($id);
        if (! $coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        return $this->successResponse($coupon, 'Coupon retrieved successfully');
    }

    /**
     * Update coupon
     */
    public function update(Request $request, $id) {
        $coupon = Coupon::find($id);
        if (! $coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        $validated = $request->validate([
            'code'                     => 'nullable|string|max:50|unique:coupons,code,' . $coupon->id,
            'name'                     => 'sometimes|string|max:255',
            'description'              => 'nullable|string',
            'discount_type'            => 'sometimes|in:percentage,fixed,bogo,free_shipping',
            'discount_value'           => 'sometimes|numeric|min:0|max:100',
            'max_discount_amount'      => 'nullable|numeric|min:0',
            'applies_to'               => 'sometimes|in:all,specific_products,specific_categories,specific_vendors',
            'eligible_items'           => 'nullable|array',
            'minimum_order_amount'     => 'nullable|numeric|min:0',
            'minimum_quantity'         => 'nullable|integer|min:0',
            'usage_limit'              => 'nullable|integer|min:1',
            'usage_limit_per_customer' => 'nullable|integer|min:0|max:10',
            'starts_at'                => 'nullable|date',
            'expires_at'               => 'nullable|date|after:starts_at',
            'is_first_time_only'       => 'boolean',
            'eligible_customer_types'  => 'nullable|array',
            'is_public'                => 'boolean',
            'is_active'                => 'boolean',
            'metadata'                 => 'nullable|array',
        ]);

        $coupon->update($validated);

        return $this->successResponse($coupon->loadCount('usage'), 'Coupon updated successfully');
    }

    /**
     * Delete coupon
     */
    public function destroy($id) {
        $coupon = Coupon::find($id);
        if (! $coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        // Check if coupon has been used
        if ($coupon->usage()->count() > 0) {
            return $this->errorResponse('Cannot delete coupon that has been used', 422);
        }

        $coupon->delete();

        return $this->successResponse(null, 'Coupon deleted successfully');
    }

    /**
     * Toggle coupon status
     */
    public function toggleStatus($id) {
        $coupon = Coupon::find($id);
        if (! $coupon) {
            return $this->errorResponse('Coupon not found', 404);
        }

        $coupon->is_active = ! $coupon->is_active;
        $coupon->save();

        return $this->successResponse($coupon, 'Coupon status updated successfully');
    }

    /**
     * Get coupon analytics
     */
    public function analytics(Request $request) {
        $analytics = $this->couponService->getAnalytics($request->all());
        return $this->successResponse($analytics, 'Analytics retrieved successfully');
    }
    public function export(Request $request) {
        $query = Coupon::withCount('usage');

        if ($request->has('status')) {
            if ($request->status === 'active') {
                $query->active();
            } elseif ($request->status === 'expired') {
                $query->where('expires_at', '<', now());
            }
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%{$search}%")
                    ->orWhere('name', 'LIKE', "%{$search}%");
            });
        }

        $coupons = $query->orderBy('created_at', 'desc')->get();

        if ($coupons->isEmpty()) {
            return $this->errorResponse('No data to export', 404);
        }

        $fileName = 'coupons_export_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($coupons) {
            $file = fopen('php://output', 'w');

            fputcsv($file, [
                'ID', 'Code', 'Name', 'Discount Type', 'Discount Value',
                'Applies To', 'Min Order', 'Usage Limit', 'Used Count',
                'Status', 'Created At', 'Expires At',
            ]);

            foreach ($coupons as $coupon) {
                fputcsv($file, [
                    $coupon->id,
                    $coupon->code,
                    $coupon->name,
                    $coupon->discount_type,
                    $coupon->discount_value,
                    $coupon->applies_to,
                    $coupon->minimum_order_amount,
                    $coupon->usage_limit ?? 'Unlimited',
                    $coupon->used_count,
                    $coupon->is_active ? 'Active' : 'Inactive',
                    $coupon->created_at?->format('Y-m-d'),
                    $coupon->expires_at?->format('Y-m-d'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
