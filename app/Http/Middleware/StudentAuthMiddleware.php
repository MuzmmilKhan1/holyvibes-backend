<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;
use Tymon\JWTAuth\Facades\JWTAuth;

class StudentAuthMiddleware
{
    public function handle(Request $request, Closure $next): JsonResponse|\Illuminate\Http\Response
    {
        try {
            $token = $request->header('token');
            if (!$token) {
                return response()->json(['error' => 'Token not provided'], 401);
            }
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('sub');
            $user = User::find($userId);
            if (!$user) {
                return response()->json(['error' => 'User not found or invalid token'], 401);
            }
            if (!$user->student_id) {
                return response()->json(['error' => 'Unauthorized user'], 403);
            }
            $request->attributes->add(['user' => $user]);
        } catch (TokenExpiredException $e) {
            return response()->json(['error' => 'Token has expired'], 401);
        } catch (TokenInvalidException $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Token processing error'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Server error'], 500);
        }

        return $next($request);
    }
}
