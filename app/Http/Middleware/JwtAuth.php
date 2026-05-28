<?php

namespace App\Http\Middleware;


use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtAuth
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate(); // ✅ FORCE JWT

            if (! $user) {
                return response()->json(['message' => 'Unauthenticated'], 401);
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Invalid token',
            ], 401);
        }

        return $next($request);
    }
}