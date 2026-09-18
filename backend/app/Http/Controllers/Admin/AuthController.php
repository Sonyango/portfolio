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

    // Login
    public function login(Request $request)
    {
        // $credentials = $request->validate([
        //     'email' => 'required|email',
        //     'password' => 'required|string',
        // ]);

        // if (!Auth::attempt($credentials)){
        //     return response()->json([
        //         'message' => 'Invalid credentials.'
        //     ], 401);
        // }

        // $user = Auth::user();
        // $token = $user->createToken('admin-token')->plainTextToken;

        // return response()->json([
        //     'message' => 'Login successful.',
        //     'token' => $token,
        //     'user' => [
        //         'id' => $user->id,
        //         'name' => $user->name,
        //         'email' => $user->email,
        //         'role' => $user->role,
        //     ]
        // ]);

        // 1. Validate input
        $credentials = $request->validate([
            'email'     => 'required|email:rfc|max:255',
            'password'  => 'required|string|min:8|max:128'
        ]);

        $ip         = $request->ip();
        $userAgent  = $request->userAgent() ?? 'Unknown';
        $email      = strtolower(trim($credentials['email']));

        // 2. Check IP-level rate limit (10 attempts per 5 min)
        $ipKey = 'login_ip_' . md5($ip);
        if (RateLimiter::tooManyAttempts($ipKey, 10)) {
            $seconds = RateLimiter::availableIn($ipKey);

            $this->recordAttempt($email, $ip, $userAgent, false, 'ip_rate_limited');
            AuditLog::record('login_ip_blocked', [
                'email'      => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'message'       => 'Too many login attempts from your location. Try again later.',
                'retry_after'   => $seconds,
                'lockout_type'  => 'ip',
            ], 429);
        }

        // 3. Find user - always run hash to prevent timing attacks
        $user = User::where('email', $email)->first();

        // Dummy hash when user not found. prevents user enumeration via timing
        if (!$user) {
            Hash::check('dummy-password-prevent-timing', '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

            RateLimiter::hit($ipKey, 300);
            $this->recordAttempt($email, $ip, $userAgent, false, 'user_not_found');

            return $this->invalidCredentialsResponse();
        }

        // 4. Check account lockout
        if ($user->isLocked()) {
            $seconds = $user->lockoutSecondsRemaining();
            $minutes = ceil($seconds / 60);

            AuditLog::record('login_blocked_lockout', [
                'user_id'    => $user->id,
                'email'      => $email,
                'ip_address' => $ip,
                'user_agent' => $userAgent,
            ]);

            return response()->json([
                'message'       => "Account temporarily locked. Try again in {$minutes} minutes(s).",
                'retry_after'   => $seconds,
                'lockout_type'  => 'account',
            ], 423);
        }

        // 5. Verify password
        if (!Hash::check($credentials['password'], $user->password)) {
            RateLimiter::hit($ipKey, 300);

            $user->incrementFailedAttempts();
            $this->recordAttempt($email, $ip, $userAgent, false, 'wrong_password');

            $failedCount = $user->fresh()->failed_attempts;

            // Alert on 3+ failures
            if ($failedCount === 3) {
                $this->brevo->sendSuspiciousLoginAlert(
                    $user->email, $user->name, $ip, $failedCount
                );
                AuditLog::record('login_suspicious_activity', [
                    'user_id'         => $user->id,
                    'email'           => $email,
                    'ip_address'      => $ip,
                    'failed_attempts' => $failedCount,
                ]);
            }

            // Notify on lockout
            if ($user->fresh()->isLocked()) {
                $minutes = ceil($user->fresh()->lockoutSecondsRemaining() / 60);
                $this->brevo->sendAccountLockout(
                    $user->email, $user->name, $ip, $minutes
                );
                AuditLog::record('login_account_locked', [
                    'user_id'    => $user->id,
                    'email'      => $email,
                    'ip_address' => $ip,
                ]);
            }
            // Generic error. Not revealing which field is wrong.
            return $this->invalidCredentialsresponse($user->fresh()->failed_attempts);
        }

        // 6. Password correct. Clear rate limiters
        RateLimiter::clear($ipKey);

        // 7. Check if MFA is enabled
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccesstoken()->delete();
        return response()->json([
            'message' => 'Logged out successfully.'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email,
                'role' => $request->user()->role,
            ]
        ]);
    }
}
