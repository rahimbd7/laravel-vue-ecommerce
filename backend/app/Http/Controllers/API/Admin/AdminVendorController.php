<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\VendorResource;
use App\Models\Vendor;
use App\Services\VendorService;
use Illuminate\Http\Request;
use App\Trait\ApiResponseTrait;
use Symfony\Component\HttpFoundation\Response;

class AdminVendorController extends Controller
{
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

    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
