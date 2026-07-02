<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(array $credentials, string $ip): array
    {
        $key = 'login:' . $ip;

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Try again in {$seconds} seconds."],
            ]);
        }

        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password_hash)) {
            RateLimiter::hit($key, 300);
            if ($user) {
                $user->increment('failed_login_attempts');
                if ($user->failed_login_attempts >= 5) {
                    $user->update(['status' => 'locked', 'locked_until' => now()->addMinutes(30)]);
                }
            }
            throw ValidationException::withMessages(['email' => ['Invalid credentials.']]);
        }

        if ($user->status === 'locked') {
            if ($user->locked_until && now()->gt($user->locked_until)) {
                $user->update(['status' => 'active', 'failed_login_attempts' => 0, 'locked_until' => null]);
            } else {
                throw ValidationException::withMessages(['email' => ['Account is locked. Contact support.']]);
            }
        }

        if (! in_array($user->status, ['active'])) {
            throw ValidationException::withMessages(['email' => ['Account is not active.']]);
        }

        RateLimiter::clear($key);
        $user->update([
            'failed_login_attempts' => 0,
            'last_login_at'         => now(),
            'last_login_ip'         => $ip,
        ]);

        $token = $user->createToken('auth-token', ['*'], now()->addHours(8))->plainTextToken;

        // Load roles + flatten permissions for the frontend
        $user->load('roles.permissions');
        $permissions = $user->getAllPermissions()->pluck('name');

        AuditLog::create([
            'user_id'     => $user->id,
            'action'      => 'login',
            'module'      => 'auth',
            'ip_address'  => $ip,
            'status'      => 'success',
            'description' => 'User logged in',
            'created_at'  => now(),
        ]);

        return [
            'user'  => array_merge($user->toArray(), ['permissions' => $permissions]),
            'token' => $token,
        ];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
        AuditLog::create([
            'user_id'    => $user->id,
            'action'     => 'logout',
            'module'     => 'auth',
            'status'     => 'success',
            'description'=> 'User logged out',
            'created_at' => now(),
        ]);
    }

    public function refreshToken(User $user): string
    {
        $user->currentAccessToken()->delete();
        return $user->createToken('auth-token', ['*'], now()->addHours(8))->plainTextToken;
    }
}
