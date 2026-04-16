<?php

namespace App\Http\Controllers\API\Vendor;

use App\Http\Controllers\Controller;
use App\Services\VendorService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Vendor\VendorApplicationRequest;
use App\Http\Resources\VendorResource;
use Symfony\Component\HttpFoundation\Response;

class VendorApplicationController extends Controller
{
    use ApiResponseTrait;
    /**
     * Display a listing of the resource.
     */

    protected VendorService $vendorService;

    public function __construct(VendorService $vendorService)
    {
        $this->vendorService = $vendorService;
        $this->middleware('auth:sanctum');
    }
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(VendorApplicationRequest $request)
    {
        //
        try {
            $vendor = $this->vendorService->apply($request->user(), $request->validated());
            return $this->successResponse(new VendorResource($vendor), 'Vendor Application Sent Successfully', 201);
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(),Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Display the specified resource.
     */


     public function applicationStatus(Request $request)
    {
        $user = $request->user();

        if (!$user->vendor) {
            return $this->successResponse([
                'status' => 'not_applied',
                'message' => 'You have not applied as a vendor yet.'
            ]);
        }

        return $this->successResponse([
            'status' => $user->vendor->is_verified ? 'verified' : 'pending',
            'vendor' => new VendorResource($user->vendor->load('user')),
            'message' => $user->vendor->is_verified
                ? 'Your vendor account is verified.'
                : 'Your application is pending for approval.'
        ]);
    }
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
