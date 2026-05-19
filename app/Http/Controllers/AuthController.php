<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\UploadPhotoRequest;
use App\Services\AuthService;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AuthController extends Controller
{
    protected AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->register($request->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'User registered successfully',
                'user' => new UserResource($result['user']),
                'permissions' => $result['user']->getAllPermissions(),
                'token' => $result['token'],
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Registration failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->email, $request->password);

        return response()->json([
            'status' => 'success',
            'message' => 'Login successful',
            'user' => new UserResource($result['user']),
            'permissions' => $result['permissions'],
            'token' => $result['token'],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('roles.permissions');
        return response()->json([
            'status' => 'success',
            'user' => new UserResource($user),
            'permissions' => $user->getAllPermissions(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logged out successfully'
        ]);
    }

    public function getMentors(): JsonResponse
    {
        $mentors = $this->authService->getMentors();

        return response()->json([
            'status' => 'success',
            'data' => UserResource::collection($mentors)
        ]);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = $this->authService->updateProfile($request->user(), $request->validated());

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile updated',
            'user'    => new UserResource($user),
        ]);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        try {
            $this->authService->changePassword($request->user(), $request->current_password, $request->password);

            return response()->json([
                'status' => 'success', 
                'message' => 'Password changed successfully!'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getCode());
        }
    }

    public function updatePhoto(UploadPhotoRequest $request): JsonResponse
    {
        $url = $this->authService->updatePhoto($request->user(), $request->file('photo'));

        return response()->json([
            'status'  => 'success',
            'message' => 'Profile photo updated!',
            'url'     => $url,
        ]);
    }
}
