<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Trait\ApiResponseTrait;
use Symfony\Component\HttpFoundation\Response;

class AdminVendorController extends Controller
{
    /**
     * Payment gateways an admin is allowed to toggle per vendor.
     * Kept in sync with the Payment Settings section of the admin settings page.
     */
    public const PAYMENT_METHODS = [
        'stripe',
        'paypal',
        'razorpay',
        'cod',
        'bank_transfer',
        'sslcommerz',
    ];

    /**
     * Display a listing of the resource.
     */

    use ApiResponseTrait;
    protected VendorService $vendorService;
    public function __construct(VendorService $vendorService)
    {
        $this->middleware(['auth:sanctum', 'role:admin']);
        $this->vendorService = $vendorService;
    }

    //get pending vendors
    public function pendingVendors()
    {
       $applications = Vendor::pending()
       ->with('user')
       ->paginate(10);
       return $this->successResponse(VendorResource::collection($applications), 'Pending Vendor Applications Retrieved Successfully');
    }

    public function approvedVendors(Vendor $vendor,Request $request)
    {
        try{
            $rejectedReason = $request->input('rejection_reason', null);
            if($vendor->is_verified){
                return $this->successResponse(new VendorResource($vendor), 'Vendor Application Already Approved');
            }
            else{
                $approvedVendor = $this->vendorService->approve($vendor, $rejectedReason);
                return $this->successResponse(new VendorResource($approvedVendor), 'Vendor Application Approved Successfully');
            }
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR, 500);
        }
    }


    public function rejectVendor(Vendor $vendor,Request $request)
    {
        $rejectedMsg = $request->input('rejection_reason');
        if($vendor->rejected_at !== null){
            return $this->errorResponse('Vendor Application Already Rejected', Response::HTTP_BAD_REQUEST, 400);
        }
        $this->vendorService->reject($vendor, $rejectedMsg);
        return $this->successResponse(null, 'Vendor Application Rejected Successfully');
    }

    /**
     * Admin vendor directory.
     * GET /api/admin/vendors?search=&status=&is_verified=&per_page=
     *
     * Powers the vendor picker on the admin Settings page. Searches the
     * business name/email/phone plus the owning user's name/email.
     */
    public function index(Request $request)
    {
        try {
            $search = trim((string) $request->input('search', ''));

            $vendors = Vendor::query()
                ->with('user')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($inner) use ($search) {
                        $inner->where('business_name', 'like', "%{$search}%")
                            ->orWhere('business_email', 'like', "%{$search}%")
                            ->orWhere('business_phone', 'like', "%{$search}%")
                            ->orWhereHas('user', function ($userQuery) use ($search) {
                                $userQuery->where('name', 'like', "%{$search}%")
                                    ->orWhere('email', 'like', "%{$search}%");
                            });
                    });
                })
                ->when($request->filled('status'), function ($query) use ($request) {
                    $query->where('status', $request->input('status'));
                })
                ->when($request->has('is_verified'), function ($query) use ($request) {
                    $query->where('is_verified', $request->boolean('is_verified'));
                })
                ->latest()
                ->paginate((int) $request->input('per_page', 15));

            return $this->paginationResponse(
                $vendors->through(function (Vendor $vendor) {
                    return new VendorResource($vendor);
                }),
                'Vendors retrieved successfully'
            );
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Single vendor with the owner account attached.
     * GET /api/admin/vendors/{vendor}
     */
    public function show(Vendor $vendor)
    {
        try {
            $vendor->loadMissing('user');

            return $this->successResponse(new VendorResource($vendor), 'Vendor retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Update a vendor's store details, commission and payment methods.
     * PUT /api/admin/vendors/{vendor}
     */
    public function update(Request $request, Vendor $vendor)
    {
        try {
            $validated = $request->validate([
                'business_name'     => 'sometimes|required|string|max:255',
                'business_email'    => [
                    'sometimes',
                    'required',
                    'email',
                    Rule::unique('vendors', 'business_email')->ignore($vendor->id),
                ],
                'business_phone'    => 'nullable|string|max:20',
                'tax_number'        => 'nullable|string|max:50',
                'website'           => 'nullable|url|max:255',
                'description'       => 'nullable|string|max:1000',
                'commission_rate'   => 'nullable|numeric|min:0|max:100',
                'status'            => 'sometimes|required|in:pending,approved,rejected',
                'rejection_reason'  => 'nullable|string|max:255',
                'is_verified'       => 'sometimes|boolean',
                'payment_methods'   => 'nullable|array',
                'payment_methods.*' => 'string|in:' . implode(',', self::PAYMENT_METHODS),
            ]);

            $data = collect($validated)->only([
                'business_name',
                'business_email',
                'business_phone',
                'tax_number',
                'website',
                'description',
                'commission_rate',
            ])->all();

            if (array_key_exists('payment_methods', $validated)) {
                // Never trust the client: keep only the gateways we support.
                $data['payment_methods'] = array_values(array_intersect(
                    self::PAYMENT_METHODS,
                    $validated['payment_methods'] ?? []
                ));
            }

            if (array_key_exists('is_verified', $validated)) {
                $data['is_verified'] = (bool) $validated['is_verified'];

                if ($data['is_verified'] && ! $vendor->is_verified) {
                    $data['verified_at'] = now();
                    $data['status'] = 'approved';
                    $data['rejected_at'] = null;
                    $data['rejection_reason'] = null;
                }
            }

            if (array_key_exists('status', $validated)) {
                $data['status'] = $validated['status'];

                if ($validated['status'] === 'approved') {
                    $data['is_verified'] = true;
                    $data['verified_at'] = $vendor->verified_at ?? now();
                    $data['rejected_at'] = null;
                    $data['rejection_reason'] = null;
                }

                if ($validated['status'] === 'rejected') {
                    $data['is_verified'] = false;
                    $data['rejected_at'] = $vendor->rejected_at ?? now();
                    $data['rejection_reason'] = $validated['rejection_reason']
                        ?? $vendor->rejection_reason;
                }
            }

            $vendor->update($data);
            $vendor->loadMissing('user');

            return $this->updatedResponse(new VendorResource($vendor), 'Vendor updated successfully');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return $this->validationErrorResponse($e->errors(), 'Validation errors occurred');
        } catch (\Exception $e) {
            return $this->serverErrorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
