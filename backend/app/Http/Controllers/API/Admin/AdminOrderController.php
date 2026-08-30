<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\TransactionLog;
use App\Services\OrderService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdminOrderController extends Controller
{
    use ApiResponseTrait;

    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
        $this->orderService = $orderService;
    }

    /**
     * Get paginated list of orders
     * GET /api/admin/orders
     */
    public function index(Request $request)
    {
        try {
            $orders = Order::with(['user', 'vendor', 'items.product'])
                ->when($request->status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($request->payment_status, function ($query, $status) {
                    return $query->where('payment_status', $status);
                })
                ->when($request->search, function ($query, $search) {
                    return $query->where(function ($q) use ($search) {
                        $q->where('order_number', 'LIKE', "%{$search}%")
                            ->orWhere('customer_name', 'LIKE', "%{$search}%")
                            ->orWhere('customer_email', 'LIKE', "%{$search}%");
                    });
                })
                ->when($request->vendor_id, function ($query, $vendorId) {
                    return $query->where('vendor_id', $vendorId);
                })
                ->when($request->from_date, function ($query, $date) {
                    return $query->whereDate('created_at', '>=', $date);
                })
                ->when($request->to_date, function ($query, $date) {
                    return $query->whereDate('created_at', '<=', $date);
                })
                ->orderBy($request->sort_by ?? 'created_at', $request->sort_order ?? 'desc')
                ->paginate($request->per_page ?? 15);

            return $this->paginationResponse($orders, 'Orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get order statistics
     * GET /api/admin/orders/stats
     */
    public function stats()
    {
        try {
            $stats = [
                'total' => Order::count(),
                'by_status' => Order::select('status', DB::raw('COUNT(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray(),
                'by_payment_status' => Order::select('payment_status', DB::raw('COUNT(*) as count'))
                    ->groupBy('payment_status')
                    ->pluck('count', 'payment_status')
                    ->toArray(),
                'pending' => Order::where('status', 'pending')->count(),
                'processing' => Order::where('status', 'processing')->count(),
                'shipped' => Order::where('status', 'shipped')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'completed' => Order::where('status', 'completed')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
                'refunded' => Order::where('status', 'refunded')->count(),
                'failed' => Order::where('status', 'failed')->count(),
                'total_revenue' => Order::where('payment_status', 'paid')->sum('grand_total') ?? 0,
                'total_orders_today' => Order::whereDate('created_at', today())->count(),
                'total_orders_this_week' => Order::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'total_orders_this_month' => Order::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->count(),
                'average_order_value' => Order::where('payment_status', 'paid')->avg('grand_total') ?? 0,
                'fulfillment_rate' => $this->calculateFulfillmentRate(),
            ];

            return $this->successResponse($stats, 'Order statistics retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get single order details
     * GET /api/admin/orders/{id}
     */
    public function show($id)
    {
        try {
            $order = Order::with([
                'user',
                'vendor',
                'items.product',
                'items.product.images',
                'payments'
            ])->find($id);

            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            return $this->successResponse($order, 'Order details retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update order status
     * PUT /api/admin/orders/{id}/status
     */
    public function updateStatus(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'status' => 'required|in:pending,processing,confirmed,shipped,delivered,completed,cancelled,refunded,failed',
                'tracking_number' => 'nullable|string|max:255',
                'carrier' => 'nullable|string|max:255',
                'notes' => 'nullable|string',
            ]);

            $order = Order::find($id);

            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            // Prevent invalid status transitions
            if (!$this->isValidStatusTransition($order->status, $validated['status'])) {
                return $this->errorResponse('Invalid status transition', 400);
            }

            $updatedOrder = $this->orderService->updateOrderStatus($order, [
                'status' => $validated['status'],
                'tracking_number' => $validated['tracking_number'] ?? null,
                'carrier' => $validated['carrier'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);

            // Log the activity
            $this->logActivity(
                'order_status_updated',
                $order->id,
                "Order #{$order->order_number} status updated to {$validated['status']}",
                $order->grand_total,
                $status = $validated['status']
            );

            return $this->successResponse($updatedOrder, 'Order status updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Cancel order
     * POST /api/admin/orders/{id}/cancel
     */
    public function cancel(Request $request, $id)
    {
        try {
            $validated = $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $order = Order::find($id);

            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            // Can only cancel pending or processing orders
            if (!in_array($order->status, ['pending', 'processing'])) {
                return $this->errorResponse('Order cannot be cancelled at this stage', 400);
            }

            $updatedOrder = $this->orderService->updateOrderStatus($order, [
                'status' => 'cancelled',
                'cancelled_at' => now(),
                'cancellation_reason' => $validated['reason'],
            ]);

            $this->logActivity(
                'order_cancelled',
                $order->id,
                "Order #{$order->order_number} cancelled by admin. Reason: {$validated['reason']}",
                $order->grand_total,
                $status = 'cancelled'
            );

            return $this->successResponse($updatedOrder, 'Order cancelled successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get order timeline/activities
     * GET /api/admin/orders/{id}/timeline
     */
    public function timeline($id)
    {
        try {
            $order = Order::find($id);

            if (!$order) {
                return $this->notFoundResponse('Order not found');
            }

            $activities = TransactionLog::where('reference_type', 'order')
                ->where('reference_id', $id)
                ->orderBy('created_at', 'desc')
                ->get();

            return $this->successResponse($activities, 'Order timeline retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Export orders to CSV
     * GET /api/admin/orders/export
     */
    public function export(Request $request)
    {
        try {
            $orders = Order::with(['user', 'vendor'])
                ->when($request->status, function ($query, $status) {
                    return $query->where('status', $status);
                })
                ->when($request->from_date, function ($query, $date) {
                    return $query->whereDate('created_at', '>=', $date);
                })
                ->when($request->to_date, function ($query, $date) {
                    return $query->whereDate('created_at', '<=', $date);
                })
                ->orderBy('created_at', 'desc')
                ->get();

            $headers = [
                'Order #', 'Customer', 'Email', 'Vendor', 'Total', 'Status',
                'Payment Status', 'Payment Method', 'Items Count', 'Created At'
            ];

            $rows = $orders->map(function ($order) {
                return [
                    $order->order_number,
                    $order->customer_name,
                    $order->customer_email,
                    $order->vendor->name ?? 'N/A',
                    $order->grand_total,
                    $order->status,
                    $order->payment_status,
                    $order->payment_method ?? 'N/A',
                    $order->items->count(),
                    $order->created_at->format('Y-m-d H:i'),
                ];
            });

            $filename = "orders_export_" . now()->format('Y-m-d') . '.csv';

            return response()->streamDownload(function () use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv',
            ]);
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Get vendor orders (filter by vendor)
     * GET /api/admin/orders/vendor/{vendorId}
     */
    public function vendorOrders($vendorId, Request $request)
    {
        try {
            $orders = $this->orderService->getVendorOrders($vendorId, $request->all());
            return $this->paginationResponse($orders, 'Vendor orders retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Calculate fulfillment rate
     */
    private function calculateFulfillmentRate(): float
    {
        $total = Order::count();
        if ($total === 0) {
            return 0;
        }

        $fulfilled = Order::whereIn('status', ['delivered', 'completed'])->count();
        return round(($fulfilled / $total) * 100, 2);
    }

    /**
     * Check if status transition is valid
     */
    private function isValidStatusTransition($currentStatus, $newStatus): bool
    {
        $validTransitions = [
            'pending' => ['processing', 'cancelled', 'failed'],
            'processing' => ['confirmed', 'cancelled', 'failed'],
            'confirmed' => ['shipped', 'cancelled'],
            'shipped' => ['delivered', 'cancelled'],
            'delivered' => ['completed', 'refunded'],
            'completed' => ['refunded'],
            'cancelled' => [],
            'refunded' => [],
            'failed' => [],
        ];

        // Allow any status if admin is overriding
        // But restrict from completed/refunded to other statuses
        if (in_array($currentStatus, ['completed', 'refunded', 'cancelled', 'failed'])) {
            return false;
        }

        return in_array($newStatus, $validTransitions[$currentStatus] ?? []);
    }

    /**
     * Log activity
     */
    private function logActivity($action, $referenceId, $description,$amount=0, $status = null)
    {
        TransactionLog::create([
            'user_id' => Auth::id(),
            'user_role' => 'admin',
            'action' => $action,
            'reference_type' => 'order',
            'reference_id' => $referenceId,
            'description' => $description,
            'status' => $status,
            'amount' => $amount,
            'metadata' => [
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ],
        ]);
    }
}
