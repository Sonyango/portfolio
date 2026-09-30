<?php

namespace App\Http\Middleware;

use App\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\PersonalAccessToken;

class EnforceIdleTimeout
{
    // Idle timeout n minutes
    private const IDLE_MINUTES = 15;

    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $token = $request->user()?->currentAccessToken();

        if (!$user || !($token instanceof PersonalAccessToken)) {
            return $next($request);
        }

        $lastActivity = $token->last_activity_at
            ? Carbon::parse($token->last_activity_at)
            : $token->created_at;

        if (!$lastActivity || $lastActivity->lte(now()->subMinutes(self::IDLE_MINUTES))) {
            $token->delete();

            AuditLog::record('auto_logout_inactivity', [
                'user_id' => $user->id,
                'email' => $user->email,
                'ip_address' => $request->ip(),
                'metadata' => [
                    'token_id' => $token->id,
                    'last_activity' => $lastActivity?->toIso8601String(),
                ],
            ]);

            return response()->json([
                'message' => 'Session expired due to inactivity.',
                'session_expired' => true,
            ], 401);
        }

        if ($request->routeIs('admin.activity-check')) {
            DB::table('personal_access_tokens')
                ->where('id', $token->id)
                ->update(['last_activity_at' => now()]);
        }

        return $next($request);
    }
}
