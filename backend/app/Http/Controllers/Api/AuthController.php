<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function login(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        $result = $this->authService->login($data, $request->ip());

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'data'    => [
                'user'  => $result['user'],
                'token' => $result['token'],
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());
        return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['roles.permissions', 'customer', 'staff.branch']);
        $permissions = $request->user()->getAllPermissions()->pluck('name');
        $data = array_merge($user->toArray(), ['permissions' => $permissions]);
        return response()->json(['success' => true, 'data' => $data]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $token = $this->authService->refreshToken($request->user());
        return response()->json(['success' => true, 'data' => ['token' => $token]]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => 'required|string',
            'password'         => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $user = $request->user();
        if (! \Illuminate\Support\Facades\Hash::check($data['current_password'], $user->password_hash)) {
            return response()->json(['success' => false, 'message' => 'Current password is incorrect.'], 422);
        }

        $user->update([
            'password_hash'       => \Illuminate\Support\Facades\Hash::make($data['password']),
            'password_changed_at' => now(),
            'force_password_change'=> false,
        ]);

        return response()->json(['success' => true, 'message' => 'Password changed successfully.']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate(['email' => 'required|email']);

        $this->authService->sendPasswordResetLink($request->email);

        return response()->json([
            'success' => true,
            'message' => 'If an account exists with this email, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email'    => 'required|email',
            'token'    => 'required|string',
            'password' => ['required', 'confirmed', Password::min(10)->mixedCase()->numbers()->symbols()],
        ]);

        $result = $this->authService->resetPassword($data['email'], $data['token'], $data['password']);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    public function enableTwoFactor(Request $request): JsonResponse
    {
        $data = $request->validate([
            'channel' => 'required|in:email,sms',
        ]);

        $result = $this->authService->enableTwoFactor($request->user(), $data['channel']);

        return response()->json($result);
    }

    public function disableTwoFactor(Request $request): JsonResponse
    {
        $success = $this->authService->disableTwoFactor($request->user());

        return response()->json([
            'success' => $success,
            'message' => $success ? '2FA disabled successfully.' : 'Failed to disable 2FA.',
        ]);
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => 'required|string|size:6',
        ]);

        $result = $this->authService->verifyOtp($request->user(), $data['code']);

        return response()->json($result);
    }
}
