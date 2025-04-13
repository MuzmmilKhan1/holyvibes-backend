<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Log;

class DecodeJwtToken
{
    public function handle(Request $request, Closure $next): JsonResponse|\Illuminate\Http\Response
    {
        try {
            $token = $request->header('token');
            if (!$token) {
                Log::warning('Token missing in request', ['path' => $request->path()]);
                return response()->json(['error' => 'Token not provided'], 401);
            }

            $user = JWTAuth::setToken($token)->authenticate();
            if (!$user) {
                Log::warning('User not found for token', ['token' => substr($token, 0, 10) . '...']);
                return response()->json(['error' => 'User not found or invalid token'], 401);
            }

            if (!$user->teacher_id) {
                Log::warning('User is not a valid teacher', ['user_id' => $user->id]);
                return response()->json(['error' => 'Unauthorized: Invalid teacher credentials'], 403);
            }

            $request->attributes->add(['user' => $user]);
            \Illuminate\Support\Facades\Auth::login($user);

        } catch (TokenExpiredException $e) {
            Log::error('Token expired', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (TokenInvalidException $e) {
            Log::error('Invalid token', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            Log::error('JWT processing error', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Token processing error'], 401);
        } catch (\Exception $e) {
            Log::error('Unexpected error in DecodeJwtToken middleware', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Server error'], 500);
        }

        return $next($request);
    }
}