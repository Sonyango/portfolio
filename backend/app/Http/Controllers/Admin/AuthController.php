<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\LoginAttempt;
use App\Models\MfaSecret;
use App\Models\MfaPending;
use App\Models\User;
use App\Services\BrevoService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function __construct(private BrevoService $brevo){}

    private function maskEmail(string $email): string
    {
        $separator = mb_strrpos($email, '@');

        if ($separator === false || $separator === 0 || $separator === mb_strlen($email) - 1) {
            return '***';
        }

        $localPart = mb_substr($email, 0, $separator);
        $domain = mb_substr($email, $separator);
        $visiblePrefix = mb_substr($localPart, 0, 1);
        $maskedLength = max(3, mb_strlen($localPart) - 1);

        return $visiblePrefix . str_repeat('*', $maskedLength) . $domain;
    }

    // Step 1: Password check
    // Always ends with an MFA challenge. Never issues token directly.
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email'    => 'required|email:rfc|max:255',
            'password' => 'required|string|min:8|max:128',
        ]);

        $ip        = $request->ip();
        $userAgent = $request->userAgent() ?? 'Unknown';
        $email     = strtolower(trim($credentials['email']));

        // IP level rate limting
        $ipKey = 'login_ip_' . md5($ip);
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $this->recordAttempt($email, $ip, $userAgent, false, 'ip_rate_limited');
            return response()->json([
                'message'      => 'Too many login attempts from your location. Try again later.',
                'retry_after'  => $seconds,
                'lockout_type' => 'ip',
            ], 429);
        }

        // Find user. Always run hash to prevent timing attacks
        $user = User::where('email', $email)->first();

        if (!$user) {
            Hash::check(
                'dummy-password-to-prevent-timing',
                '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
            );
            RateLimiter::hit($ipKey, 300);
            $this->recordAttempt($email, $ip, $userAgent, false, 'user_not_found');
            return $this->invalidCredentialsResponse();
        }

        // Account lockout check
        if ($user->isLocked()) {
            $seconds = $user->lockoutSecondsRemaining();
            AuditLog::record('login_blocked_lockout', [
                'user_id'    => $user->id,
                'email'      => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
            return response()->json([
                'message'      => 'Account temporarily locked. Try again in '
                                  . ceil($seconds / 60) . ' minute(s).',
                'retry_after'  => $seconds,
                'lockout_type' => 'account',
            ], 423);
        }

        // Password verification
        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($ipKey, 300);
            $user->incrementFailedAttempts();
            $this->recordAttempt($email, $ip, $userAgent, false, 'wrong_password');

            $fresh = $user->fresh();

            if ($fresh->failed_attempts === 3) {
                $this->brevo->sendSuspiciousLoginAlert(
                    $user->email, $user->name, $ip, $fresh->failed_attempts
                );
                AuditLog::record('login_suspicious_activity', [
                    'user_id'         => $user->id,
                    'email'           => $email,
                    'ip_address'      => $ip,
                    'failed_attempts' => $fresh,
                ]);
            }

            if ($fresh->isLocked()) {
                $minutes = ceil($fresh->lockoutSecondsRemaining() / 60);
                $this->brevo->sendAccountLockout(
                    $user->email, $user->name, $ip, $minutes
                );
                AuditLog::record('login_account_locked', [
                    'user_id'    => $user->id,
                    'emal'       => $email,
                    'ip_address' => $ip,
                ]);
            }

            return $this->invalidCredentialsResponse($fresh->failed_attempts);
        }

        // If password is correct.
        RateLimiter::clear($ipKey);

        // Determine MFA method
        // Delete any old pending challenges for this user
        MfaPending::where('user_id', $user->id)->delete();

        $hasTotpEnrolled = MfaSecret::where('user_id', $user->id)
                                    ->where('enabled', true)
                                    ->exists();

        if ($hasTotpEnrolled) {
            // TOTP path
            $pending = MfaPending::create([
                'user_id'    => $user->id,
                'token'      => Str::random(64),
                'method'     => 'totp',
                'expires_at' => now()->addMinutes(10),
            ]);

            AuditLog::record('login_mfa_totp_challenge', [
                'user_id'    => $user->id,
                'email'      => $email,
                'ip_address' => $ip,
            ]);

            return response()->json([
                'mfa_required' => true,
                'mfa_token'    => $pending->token,
                'method'       => 'totp',
                'message'      => 'Enter the 6-digit code from your authenticator app.',
            ]);
        } else {
            // Email OTP path
            // Generate a 6-digit code
            $code       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codeHash   = Hash::make($code);

            $pending = MfaPending::create([
                'user_id'         => $user->id,
                'token'           => Str::random(64),
                'method'          => 'email',
                'email_code_hash' => $codeHash,
                'expires_at'      => now()->addMinutes(10),
            ]);

            // Send code via brevo
            $sent = $this->brevo->sendMfaCode($user->email, $user->name, $code);

            AuditLog::record('login_mfa_email_challenge', [
                'user_id'    => $user->id,
                'email'      => $email,
                'ip_address' => $ip,
                'email_sent' => $sent,
            ]);

            return response()->json([
                'mfa_required'  => true,
                'mfa_token'     => $pending->token,
                'method'        => 'email',
                'message'       => 'A 6-digit verification code has been sent to '
                                    . $this->maskEmail($user->email),
                'email_hint'    => $this->maskEmail($user->email),
            ]);
        }
    }

    // Step 2: MFA Verification (TOTP or email code)
    // Only issues an access token after successful second factor.
    public function verifyMfa(Request $request)
    {
        // $request->validate([
        //     'mfa_token' => 'required|string|size:64',
        //     'totp_code' => 'required|string|size:6',
        // ]);

        // $pending = MfaPending::where('token', $request->mfa_token)
        //                         ->with('user')
        //                         ->first();

        // if (!$pending || $pending->isExpired()) {
        //     return response()->json([
        //         'message' => 'Verification session expired. Please login again.'
        //     ], 422);
        // }

        // $user   = $pending->user;
        // $secret = MfaSecret::where('user_id', $user->id)
        //                     ->where('enabled', true)
        //                     ->first();
        // if (!$secret) {
        //     return response()->json([
        //         'message' => 'MFA not configured.'
        //     ], 422);
        // }

        // $google2fa = new Google2FA();
        // $valid     = $google2fa->verifyKey($secret->secret, $request->totp_code);

        // if (!$valid) {
        //     AuditLog::record('mfa_failed', [
        //         'user_id'    => $user->id,
        //         'email'      => $user->email,
        //         'ip_address' => $request->ip(),
        //     ]);
        //     return response()->json(['message' => 'Invalid verification code.'], 422);
        // }

        // $pending->delete();

        // return $this->completeLogin($user, $request->ip(), $request->userAgent());

        $request->validate([
            'mfa_token' => 'required|string|size:64',
            'code'      => 'required|string|min:6|max:8',
        ]);

        // Retrieve pending challenge
        $pending = MfaPending::where('token', $request->mfa_token)
                                ->with('user')
                                ->first();

        if (!$pending) {
            return response()->json([
                'message' => 'Verification session not found. Please login again.',
            ], 422);
        }

        if ($pending->isExpired()) {
            $pending->delete();
            return response()->json([
                'message' => 'Verification code has expired. Please login again.',
                'expired' => true,
            ], 422);
        }

        if ($pending->hasExceededAttempts()) {
            $pending->delete();
            AuditLog::record('mfa_max_attempts_exceded', [
                'user_id'    => $pending->user_id,
                'ip_address' => $request->ip(),
            ]);
            return response()->json([
                'message'   => 'Too many failed attempts. Please login again.',
                'expired'   => true
            ], 429);
        }

        $user = $pending->user;

        // Verify based on method
        $verified = false;

        if ($pending->method === 'totp') {
            $secret = MfaSecret::where('user_id', $user->id)
                                ->where('enabled', true)
                                ->first();

            if (!$secret) {
                $pending->delete();
                return response()->json([
                    'message' => 'Authenticator not configured. Please login again.',
                ], 422);
            }

            $google2fa = new Google2FA();
            $verified  = $google2fa->verifyKey(
                $secret->secret,
                $request->code,
                2 // allow 2 time-steps window (±1 minute)
            );
        } elseif ($pending->method === 'email') {
            // Constant-time comparison via hash check
            $verified = Hash::check($request->code, $pending->email_code_hash);
        }

        // Wrong code
        if (!$verified) {
            $pending->incrementAttempts();

            $attemptsLeft = 5 - $pending->fresh()->attempts;

            AuditLog::record('mfa_failed', [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'method'     => $pending->method,
                'ip_address' => $request->ip(),
                'metadata'   => ['attempts_left' => $attemptsLeft],
            ]);

            return response()->json([
                'message'       => 'Invalid verification code.',
                'attempts_left' => max(0, $attemptsLeft),
            ], 422);
        }

        // Code verified, clean up and issue access token
        $pending->delete();

        return $this->completeLogin($user, $request->ip(), $request->userAgent() ?? '');
    }

    // Resend email code within same pending session
    public function resendEmailCode(Request $request)
    {
        $request->validate([
            'mfa_token' => 'required|string|size:64',
        ]);

        // Rate limit resends: max 3 per 10 minutes per token
        $resendKey = 'mfa_resend_' . md5($request->mfa_token);
        if (Cache::get($resendKey, 0) >= 3) {
            return response()->json([
                'message' => 'Too many resend requests. Please login again.',
            ], 429);
        }

        $pending = MfaPending::where('token', $request->mfa_token)
                                ->where('method', 'email')
                                ->with('user')
                                ->first();
        if (!$pending || $pending->isExpired()) {
            return response()->json([
                'message' => 'Session expired. Please login again.',
                'expired' => true
            ], 422);
        }

        $user       = $pending->user;
        $code       = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash   = Hash::make($code);

        // Update code and extend expiry by 10 minutes
        $pending->update([
            'email_code_hash' => $codeHash,
            'expires_at'      => now()->addMinutes(10),
            'attempts'        => 0,
        ]);

        $this->brevo->sendMfaCode($user->email, $user->name, $code);

        Cache::put($resendKey, Cache::get($resendKey, 0) + 1, now()->addMinutes(10));

        AuditLog::record('mfa_email_resent', [
            'user_id'       => $user->id,
            'email'         => $user->email,
            'ip_address'    => $request->ip(),
        ]);

        return response()->json([
            'message'       => 'A new code has been sent to ' . $this->maskEmail($user->email),
            'email_hint'    => $this->maskEmail($user->email),
        ]);
    }

    public function logout(Request $request)
    {
        // $request->user()->currentAccesstoken()->delete();
        // return response()->json([
        //     'message' => 'Logged out successfully.'
        // ]);

        $user = $request->user();

        if ($user) {
            AuditLog::record('logout', [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            $request->user()->currentAccessToken()->delete();
        }
        return response()->json(['message' => 'Logged out successfully.']);
    }

    // Get current user
    public function me(Request $request)
    {
        $user = $request->user();

        // Update last activity
        $user->update(['last_activity_at' => now()]);

        return response()->json([
            'user' => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'mfa_enabled' => $user->mfa_enabled,
                'last_login'  => $user->last_login_at?->diffForHumans(),
            ]
        ]);
    }

    // MFA Setup
    public function setupMfa(Request $request)
    {
        $user      = $request->user();
        $google2fa = new Google2FA();
        $secret    = $google2fa->generateSecretKey();

        // Store secret (not yet enabled)
        MfaSecret::updateOrCreate(
            ['user_id' => $user->id],
            ['secret'  => $secret, 'enabled' => false]
        );

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'secret'      => $secret,
            'qr_code_url' => $qrCodeUrl,
        ]);
    }

    // MFA Enable
    public function enableMfa(Request $request)
    {
        $request->validate(['totp_code' => 'required|string|size:6']);

        $user   = $request->user();
        $secret = MfaSecret::where('user_id', $user->id)->first();

        if (!$secret) {
            return response()->json(['message' => 'Setup MFA first.'], 422);
        }

        $google2fa = new Google2FA();
        $valid     = $google2fa->verifyKey($secret->secret, $request->totp_code);

        if (!$valid) {
            return response()->json(['message' => 'Invalid code. Try again.'], 422);
        }

        // Generate recovery codes
        $recoveryCodes = collect(range(1, 8))
            ->map(fn() => strtoupper(Str::random(4) . '-' . Str::random(4)))
            ->toArray();

        $secret->update([
            'enabled'        => true,
            'recovery_codes' => $recoveryCodes,
            'enabled_at'     => now(),
        ]);

        $user->update(['mfa_enabled' => true]);

        AuditLog::record('mfa_enabled', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message'   => 'Authenticator app enrolled. Future logins will use TOTP',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    // MFA Disable
    public function disableMfa(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'totp_code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $secret     = MfaSecret::where('user_id', $user->id)->first();
        $google2fa  = new Google2FA();

        if (!$secret || !$google2fa->verifyKey($secret->secret, $request->totp_code)) {
            return response()->json(['message' => 'Invalid authenticator code.'], 422);
        }

        $secret->delete();
        $user->update(['mfa_enabled' => false]);

        AuditLog::record('mfa_disabled', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Authenticator removed. Future logins will use email codes.']);
    }

    // Password reset request
    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|max:255']);

        $user = User::where('email', strtolower($request->email))->first();

        // Always return same response. Prevent email enumeration
        $genericResponse = response()->json([
            'message' => 'If that email exists, a reset link has been sent.'
        ]);

        if (!$user) return $genericResponse;

        // Rate limit: 3 per hour per email
        $cacheKey = 'pwd_reset_' . md5($user->email);
        if (Cache::get($cacheKey, 0) >= 3) {
            return $genericResponse;
        }

        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, now()->addHour());

        // Generate token
        $token      = Str::random(64);
        $resetUrl   = config('app.frontend_url', 'http://localhost:5173')
                      . '/admin/reset-password?token=' . $token
                      . '&email=' . urldecode($user->email);

        \DB::table('admin_password_resets')->insert([
            'email'      => $user->email,
            'token'      => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->brevo->sendPasswordReset($user->email, $user->name, $resetUrl);

        AuditLog::record('password_reset_requested', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return $genericResponse;
    }

    // Password reset confirm
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email'     => 'required|email',
            'token'     => 'required|string',
            'password'  => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, numbr, and special character.',
        ]);

        $reset = \DB::table('admin_password_resets')
                ->where('email', $request->email)
                ->where('created_at', '>=', now()->subHour())
                ->first();

        if (!$reset || !Hash::check($request->token, $reset->token)) {
            return response()->json([
                'message' => 'Invalid or expired reset token.'
            ], 422);
        }

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        // Check not reusing recent password
        if (Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'New password cannot be the same as your current password.'
            ], 422);
        }

        $user->update([
            'password'            => Hash::make($request->password),
            'password_changed_at' => now(),
            'failed_attempts'     => 0,
            'locked_until'        => null,
        ]);

        // Revoke all tokens. Force re-login
        $user->tokens()->delete();

        // Delete reset token
        \DB::table('admin_password_resets')
            ->where('email', $request->email)
            ->delete();

        $this->brevo->sendPasswordChanged($user->email, $user->name, $request->ip());

        AuditLog::record('password_reset_completed', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Password reset successfully. Please login.']);
    }

    // Change password (authenticated)
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password'         => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'different:current_password',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
        ], [
            'password.regex'    => 'Password must contain uppercase, lowercase, number, and special character.',
            'password.different' => 'New password must be different from current password.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update([
            'password'            => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        // Revoke all other tokens
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        $this->brevo->sendPasswordChanged($user->email, $user->name, $request->ip());

        AuditLog::record('password_changed', [
            'user_id'   => $user->id,
            'email'     => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    // Inactivity check
    public function checkActivity(Request $request)
    {
        $user            = $request->user();
        $inactivityLimit = 30; // minutes

        if ($user->last_activity_at &&
            $user->last_activity_at->lt(now()->subMinutes($inactivityLimit))) {

            // Auto logout
            $user->tokens()->delete();

            AuditLog::record('auto_logout_inactivity', [
                'user_id'    => $user->id,
                'email'      => $user->email,
                'ip_address' => $request->ip(),
            ]);

            return response()->json([
                'message'         => 'Session expired due to inactivity.',
                'session_expired' => true,
            ], 401);

            }
            $user->update(['last_activity_at' => now()]);
            return response()->json(['active' => true]);
    }

    // Private helpers
    private function completeLogin(
        User $user,
        string $ip,
        string $userAgent
    ) {
        $user->clearFailedAttempts();

        $user->update([
            'last_login_at'     => now(),
            'last_login_ip'     => $ip,
            'last_activity_at'  => now(),
        ]);

        $token = $user->createToken('admin-token')->plainTextToken;

        $this->recordAttempt($user->email, $ip, $userAgent, true, null);

        // Send login alert email
        $this->brevo->sendLoginAlert(
            $user->email,
            $user->name,
            $ip,
            substr($userAgent, 0, 100),
            now()->format('d M Y H:i:s T')
        );

        AuditLog::record('login_success', [
            'user_id'    => $user->id,
            'email'      => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'id'          => $user->id,
                'name'        => $user->name,
                'email'       => $user->email,
                'role'        => $user->role,
                'mfa_enabled' => $user->mfa_enabled,
            ],
        ]);
    }

    private function invalidCredentialsResponse(int $failedAttempts = 0): \Illuminate\Http\JsonResponse
    {
        $response = [
            'message' => 'Invalid email or password.'
        ];
        // Give a hint about remaining attempts before lockout
        if ($failedAttempts >= 2) {
            $remaining = max(0, 5 - $failedAttempts);
            if ($remaining > 0) {
                $response['attempts_remaining'] = $remaining;
                $response['warning'] = "Warning: {$remaining} attempt(s) remaining before lockout.";
            }
        }

        return response()->json($response, 401);
    }

    private function recordAttempt(
        string $email,
        string $ip,
        string $userAgent,
        bool $successful,
        ?string $reason
    ): void {
        LoginAttempt::create([
            'email'          => $email,
            'ip_address'     => $ip,
            'user_agent'     => substr($userAgent, 0, 255),
            'successful'     => $successful,
            'failure_reason' => $reason,
            'attempted_at'   => now(),
        ]);
    }
}
