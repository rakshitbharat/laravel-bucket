<?php

namespace LaraBucket\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use LaraBucket\Models\Bucket;

class AuthenticateLaraBucketClient
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        $secretKey = $request->header('X-API-KEY');

        if (empty($secretKey)) {
            $authHeader = $request->header('Authorization');
            if ($authHeader && preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
                $secretKey = $matches[1];
            }
        }

        if (empty($secretKey)) {
            return response()->json(['message' => 'Unauthorized: API Key is missing'], 401);
        }

        $bucket = Bucket::where('secret_key', $secretKey)->where('is_active', true)->first();

        if (!$bucket) {
            return response()->json(['message' => 'Unauthorized: Invalid or inactive bucket key'], 401);
        }

        // Attach the bucket to the request attributes
        $request->attributes->set('larabucket_bucket', $bucket);

        return $next($request);
    }
}
