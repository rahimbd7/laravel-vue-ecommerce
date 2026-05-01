<?php
// app/Http/Controllers/API/ProfileController.php

namespace App\Http\Controllers\API\Profile;

use App\Http\Controllers\Controller;
use App\Services\ProfileService;
use App\Trait\ApiResponseTrait;
use Illuminate\Http\Request;
use App\Http\Requests\Profile\ProfileRequest;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    use ApiResponseTrait;

    protected $profileService;

    public function __construct(ProfileService $profileService)
    {
        $this->profileService = $profileService;
    }

    /**
     * GET /api/me - Get authenticated user with profile
     */
    public function me()
    {
        $user = auth()->user();
        $profile = $this->profileService->getProfile($user);
        return $this->successResponse($profile, "Profile retrieved successfully");
    }

    /**
     * PUT /api/profile - Update user profile
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:3',
            'date_of_birth' => 'nullable|date',
            'avatar' => 'nullable|string|max:255',
        ]);

        $profile = $this->profileService->updateProfile($user, $request->all());

        return $this->successResponse($profile, "Profile updated successfully");
    }

    /**
     * PUT /api/profile/avatar - Update avatar only
     */
    public function updateAvatar(ProfileRequest $request)
    {

        $user = auth()->user();
        $result = $this->profileService->updateAvatar($user, $request->file('avatar'));

        return $this->successResponse($result, "Avatar updated successfully");
    }

    /**
     * PUT /api/change-password - Change user password
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed',
        ]);

        $user = auth()->user();
        $this->profileService->changePassword(
            $user,
            $request->current_password,
            $request->new_password
        );

        return $this->successResponse(null, "Password changed successfully");
    }
    /**
     * GET /api/profile/address - Get user's saved address
     */
    public function getAddress()
    {
        $user = auth()->user();
        $address = $this->profileService->getAddress($user);

        if (!$address) {
            return $this->successResponse(null, "No saved address found");
        }

        return $this->successResponse($address, "Address retrieved successfully");
    }

    /**
     * PUT /api/profile/address - Update user's address only
     */
    public function updateAddress(Request $request)
    {
        $request->validate([
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:3',
        ]);

        $user = auth()->user();
        $address = $this->profileService->updateAddress($user, $request->all());

        return $this->successResponse($address, "Address updated successfully");
    }
}
