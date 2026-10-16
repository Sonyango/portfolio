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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function __construct(private BrevoService $brevo) {}

    // Step 1: Password check
    // Always ends with an MFA challenge. Never issues token directly.
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email:rfc|max:255',
            'password' => 'required|string|min:8|max:128',
        ]);

        $ip = $request->ip();
        $userAgent = $request->userAgent() ?? 'Unknown';
        $email = strtolower(trim($credentials['email']));

        // IP level rate limting
        $ipKey = 'login_ip_' . md5($ip);
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);
            $this->recordAttempt($email, $ip, $userAgent, false, 'ip_rate_limited');
            return response()->json([
                'message' => 'Too many login attempts from your location. Try again later.',
                'retry_after' => $seconds,
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
                'user_id' => $user->id,
                'email' => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);
            return response()->json([
                'message' => 'Account temporarily locked. Try again in '
                    . ceil($seconds / 60) . ' minute(s).',
                'retry_after' => $seconds,
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
                    $user->email,
                    $user->name,
                    $ip,
                    $fresh->failed_attempts
                );
                AuditLog::record('login_suspicious_activity', [
                    'user_id' => $user->id,
                    'email' => $email,
                    'ip_address' => $ip,
                    'failed_attempts' => $fresh,
                ]);
            }

            if ($fresh->isLocked()) {
                $minutes = ceil($fresh->lockoutSecondsRemaining() / 60);
                $this->brevo->sendAccountLockout(
                    $user->email,
                    $user->name,
                    $ip,
                    $minutes
                );
                AuditLog::record('login_account_locked', [
                    'user_id' => $user->id,
                    'emal' => $email,
                    'ip_address' => $ip,
                ]);
            }

            return $this->invalidCredentialsResponse($fresh->failed_attempts);
        }

        // If password is correct.
        RateLimiter::clear($ipKey);

        // Delete any old pending challenges for this user
        MfaPending::where('user_id', $user->id)->delete();

        // Determine method from user preference (with fallback to email if not set)
        $method = $user->effectiveMfaMethod();

        return $this->createMfaChallenge($user, $method, $ip, $userAgent);
    }

    // Get current MFA method info for the challenge screen
    public function getMfaMethodInfo(Request $request)
    {
        $request->validate(['mfa_token' => 'required|string|size:64']);

        $pending = MfaPending::where('token', $request->mfa_token)
                            ->with('user.mfaSecret')
                            ->first();

        if (!$pending || $pending->isExpired()) {
            return response()->json([
                'message' => 'Session expired. Please login again.',
                'expired' => true
            ], 422);
        }

        $user = $pending->user;
        $hasTotpEnrolled = $user->hasTotpEnrolled();
        $availableMethods = ['email'];

        if ($hasTotpEnrolled) {
            $availableMethods[] = 'totp';
        }

        return response()->json([
            'current_method' => $pending->method,
            'preferred_method' => $user->preferred_mfa_method,
            'available_methods' => $availableMethods,
            'totp_enrolled' => $hasTotpEnrolled,
            'email_hint' => $this->maskEmail($user->email),
            'expires_at' => $pending->expires_at->toIso8601String(),
        ]);
    }

    // Switch MFA method for current pending challenge
    public function switchMfaMethod(Request $request)
    {
        $request->validate([
            'mfa_token' => 'required|string|size:64',
            'new_method' => 'required|in:email,totp',
        ]);

        $pending = MfaPending::where('token', $request->mfa_token)
                             ->with('user')
                             ->first();

        if (!$pending || $pending->isExpired()) {
            return response()->json([
                'message' => 'Session expired. Please login again.',
                'expired' => true,
            ], 422);
        }

        $user = $pending->user;
        $newMethod = $request->new_method;

        // Guard: if switching to TOTP, must be enrolled
        if ($newMethod === 'totp' && !$user->hasTotpEnrolled()) {
            return response()->json([
                'message' => 'Authenticator app is not enrolled. Please use email verification.',
            ], 422);
        }

        // If already on this method, just return current state
        if ($pending->method === $newMethod) {
            return response()->json([
                'message' => 'Already using '. $newMethod . ' verification.',
                'method'  => $newMethod,
                'email_hint' => $newMethod === 'email'
                    ? $this->maskEmail($user->email)
                    : null,
            ]);
        }

        // Update pending challenge method
        if ($newMethod === 'email') {
            // Generate and send new email code
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codeHash = Hash::make($code);

            $pending->update([
                'method' => 'email',
                'email_code_hash' => $codeHash,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]);

            $this->brevo->sendMfaCode($user->email, $user->name, $code);

            AuditLog::record('mfa_method_switched', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'metadata' => ['from' => 'totp', 'to' => 'email'],
            ]);

            return response()->json([
                'message' => 'Switched to email. Code has been sent to '
                             . $this->maskEmail($user->email),
            'method' => 'email',
            'email_hint' => $this->maskEmail($user->email),
            ]);
        } else {
            // Switch to TOTP
            $pending->update([
                'method' => 'totp',
                'email_code_hash' => null,
                'attempts' => 0,
                'expires_at' => now()->addMinutes(10),
            ]);

            AuditLog::record('mfa_method_switched', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'metadata' => ['from' => 'email', 'to' => 'totp'],
            ]);

            return response()->json([
                'message' => 'Switched to authenticator app.',
                'method' => 'totp',
            ]);
        }
    }

    // Step 2: MFA Verification (TOTP or email code)
    // Only issues an access token after successful second factor.
    public function verifyMfa(Request $request)
    {

        $request->validate([
            'mfa_token' => 'required|string|size:64',
            'code' => 'required|string|min:6|max:8',
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
            AuditLog::record('mfa_max_attempts_exceeded', [
                'user_id' => $pending->user_id,
                'ip_address' => $request->ip(),
            ]);
            return response()->json([
                'message' => 'Too many failed attempts. Please login again.',
                'expired' => true
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
                    'expired' => true,
                ], 422);
            }

            $google2fa = new Google2FA();
            $verified = $google2fa->verifyKey(
                $secret->secret,
                $request->code,
                2 // allow 2 time-steps window (±1 minute)
            );

            // Also check recovery codes
            if (!$verified) {
                $verified = $secret->verifyRecoveryCode($request->code);
            }

        } elseif ($pending->method === 'email') {
            // Constant-time comparison via hash check
            $verified = Hash::check($request->code, $pending->email_code_hash);
        }

        // Wrong code
        if (!$verified) {
            $pending->incrementAttempts();

            $attemptsLeft = 5 - $pending->fresh()->attempts;

            AuditLog::record('mfa_failed', [
                'user_id' => $user->id,
                'email' => $user->email,
                'method' => $pending->method,
                'ip_address' => $request->ip(),
                'metadata' => ['attempts_left' => $attemptsLeft],
            ]);

            return response()->json([
                'message' => 'Invalid verification code.',
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

        $user = $pending->user;
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $codeHash = Hash::make($code);

        // Update code and extend expiry by 10 minutes
        $pending->update([
            'email_code_hash' => $codeHash,
            'expires_at' => now()->addMinutes(10),
            'attempts' => 0,
        ]);

        $this->brevo->sendMfaCode($user->email, $user->name, $code);

        Cache::put($resendKey, Cache::get($resendKey, 0) + 1, now()->addMinutes(10));

        AuditLog::record('mfa_email_resent', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'A new code has been sent to ' . $this->maskEmail($user->email),
            'email_hint' => $this->maskEmail($user->email),
        ]);
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            AuditLog::record('logout', [
                'user_id' => $user->id,
                'email' => $user->email,
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
        $user = $request->user()->load('mfaSecret');
        $user->update(['last_activity_at' => now()]);

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'mfa_enabled' => $user->mfa_enabled,
                'preferred_mfa_method' => $user->preferred_mfa_method,
                'totp_enrolled' => $user->hasTotpEnrolled(),
                'last_login' => $user->last_login_at?->diffForHumans(),
                'last_login_ip' => $user->last_login_ip,
                'password_changed_at' => $user->password_changed_at?->format('d M Y H:i:s T'),
            ]
        ]);
    }

    // Profile update
    public function updateProfile(Request $request)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
        ]);

        $request->user()->update($data);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => ['name' => $request->user()->fresh()->name],
        ]);
    }

    // MFA Preference
    public function setMfaPreference(Request $request)
    {
        $request->validate([
            'method' => 'required|in:email,totp',
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $method = $request->input('method');

        if ($method === 'totp' && !$user->hasTotpEnrolled()) {
            return response()->json([
                'message' => 'You must enroll an authenticator app before setting it as your preferred method.'
            ], 422);
        }

        $user->update(['preferred_mfa_method' => $method]);

        AuditLog::record('mfa_preference_changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
            'metadata' => ['new_method' => $method],
        ]);

        return response()->json([
            'message' => 'MFA preference updated.',
            'preferred_method' => $method,
        ]);
    }

    // TOTP Setup - Step 1: Generate secret Plus QR code
    // Does not replace active secret until confirmed
    public function setupMfa(Request $request)
    {
        $user = $request->user();
        $google2fa = new Google2FA();
        $newSecret = $google2fa->generateSecretKey(32);

        // Get or create secret record without touching the active secret.
        $mfaRecord = MfaSecret::firstOrCreate(
            ['user_id' => $user->id],
            ['secret' => '', 'enabled' => false]
        );

        // Store new setup in pending secret field. Do not overwrite active secret until new one is confirmed.
        $mfaRecord->update([
            'pending_secret' => $newSecret,
            'setup_started_at' => now(),
        ]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name') . ' Admin',
            $user->email,
            $newSecret
        );

        AuditLog::record('mfa_setup_initiated', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'secret' => $newSecret,
            'qr_code_url' => $qrCodeUrl,
            'totp_enrolled' => $mfaRecord->enabled,
            'setup_started_at' => $mfaRecord->setup_started_at?->toIso8601String(),
        ]);
    }

    // TOTP Setup - step 2: verify code and activate
    public function enableMfa(Request $request)
    {
        $request->validate([
            'totp_code' => 'required|string|size:6',
            'password' => 'required|string',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $mfaRecord = MfaSecret::where('user_id', $user->id)->first();

        if (!$mfaRecord || !$mfaRecord->hasPendingSetup()) {
            return response()->json([
                'message' => 'No pending authentcator setup found. Please start the setup process again.',
            ], 422);
        }

        $google2fa = new Google2FA();
        if (!$google2fa->verifyKey($mfaRecord->pending_secret, $request->totp_code)) {
            return response()->json([
                'message' => 'Invalid code. Ensure your authenticator app is synced and try again.',
            ], 422);
        }

        // Code verified. Promote pending secret to active secret
        $recoveryCodes = $this->generateRecoveryCodes();
        $recoveryCodesHashed = MfaSecret::hashRecoveryCodes($recoveryCodes);

        $mfaRecord->update([
            'secret' => $mfaRecord->pending_secret,
            'pending_secret' => null,
            'setup_started_at' => null,
            'enabled' => true,
            'enabled_at' => now(),
            'recovery_codes' => $recoveryCodesHashed,
            'recovery_codes_hashed' => $recoveryCodesHashed,

        ]);

        $user->update([
            'mfa_enabled' => true,
            'preferred_mfa_method' => 'totp',
        ]);

        AuditLog::record('mfa_enabled', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Authenticator app enabled. TOTP is now your default MFA method.',
            'recovery_codes' => $recoveryCodes,
        ]);
    }

    // TOTP Disable / Remove
    public function disableMfa(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
            'totp_code' => 'required|string|min:6|max:8',
        ]);

        $user = $request->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Incorrect password.'], 422);
        }

        $mfaRecord = MfaSecret::where('user_id', $user->id)
                              ->where('enabled', true)
                              ->first();

        if (!$mfaRecord) {
            return response()->json(['message' => 'No active authenticator app found.'], 422);
        }

        $google2fa = new Google2FA();
        $validTotp = $google2fa->verifyKey($mfaRecord->secret, $request->totp_code, 2);
        $validRecovery = !$validTotp
            ? $mfaRecord->verifyRecoveryCode($request->totp_code)
            : false;

        if (!$validTotp && !$validRecovery) {
            return response()->json(['message' => 'Invalid authenticator code.'], 422);
        }

        $mfaRecord->delete();

        $user->update([
            'mfa_enabled' => false,
            'preferred_mfa_method' => 'email',
        ]);

        AuditLog::record('mfa_disabled', [
            'user_id' => $user->id,
            'email' => $user->email,
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

        if (!$user)
            return $genericResponse;

        // Rate limit: 3 per hour per email
        $cacheKey = 'pwd_reset_' . md5($user->email);
        if (Cache::get($cacheKey, 0) >= 3) {
            return $genericResponse;
        }

        Cache::put($cacheKey, Cache::get($cacheKey, 0) + 1, now()->addHour());

        // Generate token
        $token = Str::random(64);
        $resetUrl = config('app.frontend_url', 'http://localhost:5173')
            . '/admin/reset-password?token=' . $token
            . '&email=' . urlencode($user->email);

        \DB::table('admin_password_resets')->insert([
            'email' => $user->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $this->brevo->sendPasswordReset($user->email, $user->name, $resetUrl);

        AuditLog::record('password_reset_requested', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return $genericResponse;
    }

    // Password reset confirm
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, numbr, and special character.',
        ]);

        $reset = DB::table('admin_password_resets')
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
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
            'failed_attempts' => 0,
            'locked_until' => null,
        ]);

        // Revoke all tokens. Force re-login
        $user->tokens()->delete();

        // Delete reset token
        DB::table('admin_password_resets')
            ->where('email', $request->email)
            ->delete();

        $this->brevo->sendPasswordChanged($user->email, $user->name, $request->ip());

        AuditLog::record('password_reset_completed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Password reset successfully. Please login.']);
    }

    // Change password (authenticated)
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => [
                'required',
                'string',
                'min:12',
                'confirmed',
                'different:current_password',
                'regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\W_]).+$/',
            ],
        ], [
            'password.regex' => 'Password must contain uppercase, lowercase, number, and special character.',
            'password.different' => 'New password must be different from current password.',
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Current password is incorrect.'], 422);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_changed_at' => now(),
        ]);

        // Revoke all other tokens
        $user->tokens()->where('id', '!=', $request->user()->currentAccessToken()->id)->delete();

        $this->brevo->sendPasswordChanged($user->email, $user->name, $request->ip());

        AuditLog::record('password_changed', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $request->ip(),
        ]);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    // Inactivity check - middleware already enforces timeout before this runs
    // This endpoint simply confirms the session is still valid
    public function checkActivity(Request $request)
    {
        return response()->json([
            'active' => true,
            'user_name' => $request->user()->name,
        ]);
    }

    // Private helpers
    private function createMfaChallenge(
        User $user,
        string $method,
        string $ip,
        string $userAgent
    ){
        MfaPending::where('user_id', $user->id)->delete();

        if ($method === 'totp') {
            $pending = MfaPending::create([
                'user_id' => $user->id,
                'token' => Str::random(64),
                'method' => 'totp',
                'expires_at' => now()->addMinutes(10),
            ]);

            AuditLog::record('login_mfa_totp_challenge', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'mfa_required' => true,
                'mfa_token' => $pending->token,
                'method' => 'totp',
                'message' => 'Please enter the code from your authenticator app.',
            ]);
        } else {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $codeHash = Hash::make($code);

            $pending = MfaPending::create([
                'user_id' => $user->id,
                'token' => Str::random(64),
                'method' => 'email',
                'email_code_hash' => $codeHash,
                'expires_at' => now()->addMinutes(10),
            ]);

            $sent = $this->brevo->sendMfaCode($user->email, $user->name, $code);

            AuditLog::record('login_mfa_email_challenge', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $ip,
                'email_sent' => $sent,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'mfa_required' => true,
                'mfa_token' => $pending->token,
                'method' => 'email',
                'message' => 'A code has been sent to ' . $this->maskEmail($user->email),
                'email_hint' => $this->maskEmail($user->email),
            ]);
        }
    }
    private function generateRecoveryCodes(): array
    {
        return array_map(
            static fn (): string => strtoupper(Str::random(10)),
            range(1, 10)
        );
    }

    private function completeLogin(
        User $user,
        string $ip,
        string $userAgent
    ) {
        $user->clearFailedAttempts();

        $user->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
            'last_activity_at' => now(),
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
            'user_id' => $user->id,
            'email' => $user->email,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
        ]);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'mfa_enabled' => $user->mfa_enabled,
                'preferred_mfa_method' => $user->preferred_mfa_method,
                'totp_enrolled' => $user->hasTotpEnrolled(),
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
            'email' => $email,
            'ip_address' => $ip,
            'user_agent' => substr($userAgent, 0, 255),
            'successful' => $successful,
            'failure_reason' => $reason,
            'attempted_at' => now(),
        ]);
    }

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
}
