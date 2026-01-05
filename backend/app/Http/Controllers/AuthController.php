<?php
namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Trait\ApiResponseTrait;
class AuthController extends Controller {
    protected AuthService $authService;
    use ApiResponseTrait;

    public function __construct(AuthService $authService) {
        $this->authService = $authService;
    }
    public function register(RegisterRequest $request): JsonResponse {
        DB::beginTransaction();
        try {
            $data  = $request->validated();
            $user  = $this->authService->register($data);
            $token = $user->createToken('auth_token')->plainTextToken;
            DB::commit();
            return $this->authResponse($user = new UserResource($user), $token, 'User registered successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->errorResponse('Registration failed: ' . $e->getMessage(), 500);
        }
    }
    public function login(LoginRequest $request): JsonResponse {
        $credentials = $request->only('email', 'password');
        if (! $this->authService->attemptLogin($credentials)) {
            return response()->json(['message' => 'Invalid login credentials'], 401);
        }
        $user  = $this->authService->getUserByEmail($credentials['email']);
        $token = $user->createToken('auth_token')->plainTextToken;
        return $this->authResponse($user = new UserResource($user), 'Bearer', $token, 'Login successful', 200);
    }
    public function logout(Request $request): JsonResponse {
        $user = $request->user();
        $this->authService->logoutCurrentDevice($user);
        return response()->json(['message' => 'Logged out from current device'], 200);
    }
    public function logoutAllDevices(Request $request): JsonResponse {
        $user = $request->user();
        $this->authService->logoutAllDevices($user);
        return response()->json(['message' => 'Logged out from all devices'], 200);
    }

}
