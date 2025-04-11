<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class DecodeJwtToken
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->header('token');

        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }

        try {
            // Decode the token and retrieve the payload
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('sub'); // or 'user_id'

            // Log the user_id
            Log::info('JWT Token decoded', ['user_id' => $userId]);

            // Pass the user_id to the request
            $request->merge(['user_id' => $userId]);

        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Proceed to the next middleware or controller
        return $next($request);
    }
}
