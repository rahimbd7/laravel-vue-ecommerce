<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    public function getProfile(User $user)
    {
        return $user->load('profile');
    }

    public function updateProfile(User $user, array $data)
    {
        // Update user fields
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }

        // Simple email update (no verification for now)
        if (isset($data['email']) && $data['email'] !== $user->email) {
            // Check if email exists
            if (User::where('email', $data['email'])->where('id', '!=', $user->id)->exists()) {
                throw new \Exception('Email already taken');
            }
            $user->email = $data['email'];
        }

        $user->save();

        // Update or create profile
        $profileData = array_intersect_key($data, array_flip([
            'phone', 'address', 'city', 'state',
            'postal_code', 'country', 'date_of_birth', 'avatar'
        ]));

        $profile = $user->profile;
        if ($profile) {
            $profile->update($profileData);
        } else {
            $user->profile()->create($profileData);
        }

        return $user->load('profile');
    }

    public function updateAvatar(User $user, $avatarFile)
    {
        $profile = $user->profile;

        // Delete old avatar if exists
        if ($profile && $profile->avatar) {
            Storage::disk('public')->delete($profile->avatar);
        }

        // Store new avatar
        $path = $avatarFile->store('avatars', 'public');

        $avatarData = ['avatar' => $path];

        if ($profile) {
            $profile->update($avatarData);
        } else {
            $user->profile()->create($avatarData);
        }

        // Return full URL
        return [
            'avatar' => Storage::url($path),
            'avatar_path' => $path,
        ];
    }

    /**
     * Update avatar from URL (for backward compatibility)
     */
    public function updateAvatarFromUrl(User $user, string $avatarUrl)
    {
        $profile = $user->profile;

        if ($profile) {
            $profile->update(['avatar' => $avatarUrl]);
        } else {
            $user->profile()->create(['avatar' => $avatarUrl]);
        }

        return ['avatar' => $avatarUrl];
    }

    public function updateAddress(User $user, array $data)
    {
        $addressData = array_intersect_key($data, array_flip([
            'address', 'city', 'state', 'postal_code', 'country'
        ]));

        $profile = $user->profile;
        if ($profile) {
            $profile->update($addressData);
        } else {
            $user->profile()->create($addressData);
        }

        return $addressData;
    }

    public function getAddress(User $user)
    {
        $profile = $user->profile;
        if (!$profile || !$profile->hasCompleteAddress()) {
            return null;
        }

        return [
            'address' => $profile->address,
            'city' => $profile->city,
            'state' => $profile->state,
            'postal_code' => $profile->postal_code,
            'country' => $profile->country,
            'full_address' => $profile->full_address,
        ];
    }

    public function changePassword(User $user, string $currentPassword, string $newPassword)
    {
        if (!Hash::check($currentPassword, $user->password)) {
            throw new \Exception('Current password is incorrect');
        }

        $user->password = Hash::make($newPassword);
        $user->save();

        return true;
    }
}
