<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CouponUsage;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CouponUsageController extends Controller
{
    use ApiResponseTrait;

    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Get coupon usage history for authenticated user
     */
    public function history(Request $request)
    {
        $usage = CouponUsage::where('user_id', $request->user()->id)
            ->with(['coupon', 'order'])
            ->notReversed()
            ->orderBy('used_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginationResponse($usage, 'Coupon history retrieved successfully');
    }

    /**
     * Get coupon usage details by ID
     */
    public function show($id)
    {
        $usage = CouponUsage::with(['coupon', 'order', 'user'])
            ->find($id);

        if (!$usage) {
            return $this->errorResponse('Coupon usage record not found', 404);
        }

        return $this->successResponse($usage, 'Coupon usage details retrieved successfully');
    }

    /**
     * Get all coupon usage records (Admin only)
     */
    public function index(Request $request)
    {

        $query = CouponUsage::with(['coupon', 'user', 'order'])
            ->notReversed();

        // Apply filters
        if ($request->has('coupon_id')) {
            $query->where('coupon_id', $request->coupon_id);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('date_from')) {
            $query->whereDate('used_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('used_at', '<=', $request->date_to);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->whereHas('coupon', function ($cq) use ($search) {
                    $cq->where('code', 'LIKE', "%{$search}%")
                        ->orWhere('name', 'LIKE', "%{$search}%");
                })->orWhereHas('user', function ($uq) use ($search) {
                    $uq->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('email', 'LIKE', "%{$search}%");
                });
            });
        }

        $usage = $query->orderBy('used_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return $this->paginationResponse($usage, 'Coupon usage records retrieved successfully');
    }

    /**
     * Get coupon usage statistics (Admin only)
     */
    public function stats(Request $request)
    {

        $query = CouponUsage::notReversed();

        if ($request->has('date_from')) {
            $query->whereDate('used_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('used_at', '<=', $request->date_to);
        }

        $stats = [
            'total_uses' => $query->count(),
            'total_discount_amount' => $query->sum('discount_amount'),
            'average_discount' => $query->avg('discount_amount') ?? 0,
            'total_original_subtotal' => $query->sum('original_subtotal'),
            'total_discounted_total' => $query->sum('discounted_total'),
            'by_coupon' => (clone $query)->select(
                'coupon_id',
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->with('coupon')
            ->groupBy('coupon_id')
            ->orderBy('uses', 'desc')
            ->limit(10)
            ->get(),
            'by_user' => (clone $query)->select(
                'user_id',
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->with('user')
            ->groupBy('user_id')
            ->orderBy('uses', 'desc')
            ->limit(10)
            ->get(),
            'daily' => (clone $query)->select(
                DB::raw('DATE(used_at) as date'),
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get(),
        ];

        return $this->successResponse($stats, 'Coupon usage statistics retrieved successfully');
    }

    /**
     * Get vendor coupon usage stats (Vendor only)
     */
    public function vendorStats(Request $request)
    {
        $vendor = Auth::vendor(); // <->
$vendorId = $vendor ? $vendor->id : null;

        if (!$vendorId) {
            return $this->errorResponse('Vendor not found', 404);
        }

        // Get coupon IDs belonging to this vendor
        $couponIds = \App\Models\Coupon::where('vendor_id', $vendorId)
            ->orWhere('created_by_id', $vendorId)
            ->pluck('id');

        if ($couponIds->isEmpty()) {
            return $this->successResponse([
                'total_uses' => 0,
                'total_discount' => 0,
                'by_coupon' => [],
            ], 'No coupon usage found for your store');
        }

        $query = CouponUsage::whereIn('coupon_id', $couponIds)
            ->notReversed();

        if ($request->has('date_from')) {
            $query->whereDate('used_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('used_at', '<=', $request->date_to);
        }

        $stats = [
            'total_uses' => $query->count(),
            'total_discount' => $query->sum('discount_amount'),
            'average_discount' => $query->avg('discount_amount') ?? 0,
            'by_coupon' => (clone $query)->select(
                'coupon_id',
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->with('coupon')
            ->groupBy('coupon_id')
            ->orderBy('uses', 'desc')
            ->get(),
            'by_date' => (clone $query)->select(
                DB::raw('DATE(used_at) as date'),
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(discount_amount) as total_discount')
            )
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->limit(30)
            ->get(),
        ];

        return $this->successResponse($stats, 'Vendor coupon usage statistics retrieved successfully');
    }

    /**
     * Reverse/void a coupon usage (Admin only)
     */
    public function reverse($id)
    {


        $usage = CouponUsage::find($id);

        if (!$usage) {
            return $this->errorResponse('Coupon usage record not found', 404);
        }

        if ($usage->is_reversed) {
            return $this->errorResponse('Coupon usage already reversed', 422);
        }

        $usage->is_reversed = true;
        $usage->reversed_at = now();
        $usage->save();

        // Decrement coupon usage count
        $coupon = \App\Models\Coupon::find($usage->coupon_id);
        if ($coupon) {
            $coupon->used_count = max(0, $coupon->used_count - 1);
            $coupon->save();
        }

        return $this->successResponse($usage, 'Coupon usage reversed successfully');
    }

    /**
     * Export coupon usage data (Admin only)
     */
    public function export(Request $request)
    {


        $query = CouponUsage::with(['coupon', 'user'])
            ->notReversed();

        if ($request->has('date_from')) {
            $query->whereDate('used_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('used_at', '<=', $request->date_to);
        }

        $usage = $query->orderBy('used_at', 'desc')->get();

        if ($usage->isEmpty()) {
            return $this->errorResponse('No data to export', 404);
        }

        $fileName = 'coupon_usage_export_' . date('Y-m-d') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ];

        $callback = function () use ($usage) {
            $file = fopen('php://output', 'w');

            // Add headers
            fputcsv($file, [
                'ID',
                'Coupon Code',
                'Coupon Name',
                'User Name',
                'User Email',
                'Original Subtotal',
                'Discount Amount',
                'Discounted Total',
                'Used At',
                'Source',
            ]);

            // Add rows
            foreach ($usage as $record) {
                fputcsv($file, [
                    $record->id,
                    $record->coupon->code ?? 'N/A',
                    $record->coupon->name ?? 'N/A',
                    $record->user->name ?? 'N/A',
                    $record->user->email ?? 'N/A',
                    $record->original_subtotal,
                    $record->discount_amount,
                    $record->discounted_total,
                    $record->used_at->format('Y-m-d H:i:s'),
                    $record->source,
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
