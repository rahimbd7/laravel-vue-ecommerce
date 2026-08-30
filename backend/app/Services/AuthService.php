<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthService
{
    public function register(array $data): User
    {
        $data['password'] = Hash::make($data['password']);
       $user =  User::create($data);
       $user->profile()->create([]);
       return $user;
    }

    public function attemptLogin(array $credentials): bool
    {
        // Logic for user login attempt
        return Auth::attempt($credentials);
    }

    public function getUserByEmail(string $email): ?User
    {
        // Get user by email
        return User::where('email', $email)->first();
    }

    // Optional: Get authenticated user
    public function getAuthenticatedUser(): ?User
    {
        return Auth::user();
    }

    // Optional: Logout current device
    public function logoutCurrentDevice($user): void
    {
        $user->currentAccessToken()->delete();
    }

    // Optional: Logout all devices
    public function logoutAllDevices($user): void
    {
        $user->tokens()->delete();
    }
}
