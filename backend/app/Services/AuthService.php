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

    public function sendPasswordResetLink(string $email): bool
    {
        $user = User::where('email', $email)->first();
        
        if (!$user) {
            // Don't reveal if email exists for security
            return true;
        }

        $token = \Illuminate\Support\Str::random(60);
        
        // Delete previous tokens
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Create new token
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
            'email' => $email,
            'token' => \Illuminate\Support\Facades\Hash::make($token),
            'created_at' => now(),
        ]);

        // Send email with reset link
        try {
            \Illuminate\Support\Facades\Mail::send('emails.password-reset', [
                'token' => $token,
                'email' => $email,
                'user' => $user,
                'reset_link' => route('password.reset', ['token' => $token, 'email' => $email]),
            ], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Reset Your Password - Bank Management System');
            });

            \Illuminate\Support\Facades\Log::info('Password reset email sent', ['email' => $email]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Password reset email failed', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);
        }

        return true;
    }

    public function resetPassword(string $email, string $token, string $password): array
    {
        $resetRecord = \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRecord) {
            return ['success' => false, 'message' => 'Invalid password reset request.'];
        }

        // Check token expiry (4 hours)
        $createdAt = \Illuminate\Support\Carbon::parse($resetRecord->created_at);
        if ($createdAt->addHours(4)->isPast()) {
            \Illuminate\Support\Facades\DB::table('password_reset_tokens')
                ->where('email', $email)
                ->delete();
            return ['success' => false, 'message' => 'Password reset link has expired.'];
        }

        // Verify token
        if (!Hash::check($token, $resetRecord->token)) {
            return ['success' => false, 'message' => 'Invalid password reset token.'];
        }

        $user = User::where('email', $email)->first();
        if (!$user) {
            return ['success' => false, 'message' => 'User not found.'];
        }

        // Update password
        $user->update([
            'password_hash' => Hash::make($password),
            'password_changed_at' => now(),
            'force_password_change' => false,
            'failed_login_attempts' => 0,
            'locked_until' => null,
            'status' => 'active',
        ]);

        // Delete token after successful reset
        \Illuminate\Support\Facades\DB::table('password_reset_tokens')
            ->where('email', $email)
            ->delete();

        // Log the action
        AuditLog::create([
            'user_id' => $user->id,
            'action' => 'password_reset',
            'module' => 'auth',
            'status' => 'success',
            'description' => 'Password reset completed via forgot password',
            'created_at' => now(),
        ]);

        return ['success' => true, 'message' => 'Password has been reset successfully.'];
    }

    public function enableTwoFactor(User $user, string $channel = 'email'): array
    {
        $secret = \PragmaRX\Google2FA\Google2FA::generateSecretKey();
        
        $user->update([
            'two_factor_enabled' => true,
            'two_factor_type' => $channel,
            'two_factor_secret' => \Illuminate\Support\Facades\Crypt::encryptString($secret),
        ]);

        // Send OTP
        $this->sendOtp($user, $channel);

        return [
            'success' => true,
            'message' => "OTP sent to {$channel}.",
            'secret' => $secret,
        ];
    }

    public function disableTwoFactor(User $user): bool
    {
        return $user->update([
            'two_factor_enabled' => false,
            'two_factor_type' => null,
            'two_factor_secret' => null,
        ]);
    }

    public function sendOtp(User $user, string $channel = 'email'): bool
    {
        // Delete expired OTPs
        \Illuminate\Support\Facades\DB::table('otp_tokens')
            ->where('user_id', $user->id)
            ->where('expires_at', '<', now())
            ->delete();

        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        \Illuminate\Support\Facades\DB::table('otp_tokens')->insert([
            'user_id' => $user->id,
            'code' => $code,
            'channel' => $channel,
            'created_at' => now(),
            'expires_at' => now()->addMinutes(5),
        ]);

        if ($channel === 'email') {
            try {
                \Illuminate\Support\Facades\Mail::send('emails.otp-code', [
                    'otp' => $code,
                    'user' => $user,
                ], function ($message) use ($user) {
                    $message->to($user->email)
                        ->subject('Your OTP - Bank Management System');
                });
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('OTP email failed', ['error' => $e->getMessage()]);
                return false;
            }
        }
        // TODO: Implement SMS sending

        return true;
    }

    public function verifyOtp(User $user, string $code): array
    {
        $otp = \Illuminate\Support\Facades\DB::table('otp_tokens')
            ->where('user_id', $user->id)
            ->where('code', $code)
            ->where('verified_at', null)
            ->where('expires_at', '>', now())
            ->latest()
            ->first();

        if (!$otp) {
            // Increment attempts
            \Illuminate\Support\Facades\DB::table('otp_tokens')
                ->where('user_id', $user->id)
                ->where('code', '<>', $code)
                ->increment('attempts');

            return ['success' => false, 'message' => 'Invalid or expired OTP.'];
        }

        // Mark as verified
        \Illuminate\Support\Facades\DB::table('otp_tokens')
            ->where('id', $otp->id)
            ->update(['verified_at' => now()]);

        // If this was 2FA verification, update user
        if ($user->two_factor_enabled) {
            // 2FA verified - can proceed with login
        }

        return ['success' => true, 'message' => 'OTP verified successfully.'];
    }
}
