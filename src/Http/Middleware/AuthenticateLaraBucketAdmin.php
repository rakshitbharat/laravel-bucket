<?php

namespace LaraBucket\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateLaraBucketAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $adminEmail = config('larabucket.server.admin_email', 'super@admin.com');
        $adminPassword = config('larabucket.server.admin_password', 'password');
        $expectedToken = hash_hmac('sha256', $adminEmail, $adminPassword);

        $token = $request->header('X-Admin-Token');
        if (empty($token)) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $token = $matches[1];
            }
        }

        if (empty($token) || $token !== $expectedToken) {
            return response()->json(['message' => 'Unauthorized admin access'], 401);
        }

        return $next($request);
    }
}
