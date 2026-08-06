<?php

namespace App\Http\Controllers\API\Vendor;
use App\Http\Controllers\Controller;
use App\Services\VendorProfileService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class VendorProfileController extends Controller
{
    use ApiResponseTrait;

    protected $vendorProfileService;

    public function __construct(VendorProfileService $vendorProfileService)
    {
        $this->vendorProfileService = $vendorProfileService;
        $this->middleware(['auth:sanctum', 'role:vendor']);
    }

    public function index()
    {
        try {
            $user = Auth::user();
            $profile = $this->vendorProfileService->getProfile($user);
            return $this->successResponse($profile, "Vendor profile retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'business_name' => 'sometimes|string|max:255',
            'business_email' => ['sometimes', 'email', Rule::unique('vendors')->ignore($user->vendor?->id)],
            'business_phone' => 'nullable|string|max:20',
            'tax_number' => 'nullable|string|max:50',
            'website' => 'nullable|url|max:255',
            'description' => 'nullable|string|max:1000',
        ]);

        try {
            $profile = $this->vendorProfileService->updateProfile($user, $request->all());
            return $this->successResponse($profile, "Vendor profile updated successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateLogo(Request $request)
    {
        $request->validate([
            'logo' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        try {
            $user = Auth::user();
            $result = $this->vendorProfileService->updateLogo($user, $request->file('logo'));
            return $this->successResponse($result, "Vendor logo updated successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }

    public function updateLogoFromUrl(Request $request)
    {
        $request->validate([
            'logo_url' => 'required|string|url|max:500',
        ]);

        try {
            $user = Auth::user();
            $logoUrl = $request->logo_url;
            $result = $this->vendorProfileService->updateLogoFromUrl($user, $logoUrl);
            return $this->successResponse($result, "Vendor logo updated successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
    public function stats()
    {
        try {
            $user = Auth::user();
            $stats = $this->vendorProfileService->getVendorStats($user);
            return $this->successResponse($stats, "Vendor statistics retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function analytics()
    {
        try {
            $user = Auth::user();
            $analytics = $this->vendorProfileService->getAnalytics($user);
            return $this->successResponse($analytics, "Vendor analytics retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function getShipping()
    {
        try {
            $user = Auth::user();
            $settings = $this->vendorProfileService->getShippingSettings($user);
            return $this->successResponse($settings, "Shipping settings retrieved successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    public function updateShipping(Request $request)
    {
        $request->validate([
            'free_shipping_threshold' => 'nullable|numeric|min:0',
            'default_shipping_rate' => 'nullable|numeric|min:0',
            'shipping_zones' => 'nullable|array',
            'shipping_zones.*.name' => 'required_with:shipping_zones|string|max:255',
            'shipping_zones.*.rate' => 'required_with:shipping_zones|numeric|min:0',
            'delivery_min_days' => 'nullable|integer|min:0',
            'delivery_max_days' => 'nullable|integer|min:0|gte:delivery_min_days',
        ]);

        try {
            $user = Auth::user();
            $settings = $this->vendorProfileService->updateShippingSettings($user, $request->all());
            return $this->successResponse($settings, "Shipping settings updated successfully");
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 400);
        }
    }
}
