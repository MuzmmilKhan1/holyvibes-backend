<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class DecodeJwtToken
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $token = $request->header('token');
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('sub');
            $user = User::find($userId);
            if (!$user || !$user->teacher_id) {
                return response()->json(['error' => 'Unauthorized or invalid teacher'], 403);
            }
          
            $request->merge(['user' => $user]);

        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }

        // Proceed to the next middleware or controller
        return $next($request);
    }
}
