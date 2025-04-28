<?php

namespace App\Http\Controllers;

use App\Models\PasswordReset;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);
        $user = User::where('email', $request->email)->first();
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials',
            ], 401);
        }
        $status = null;
        if ($user->teacher_id !== null) {
            $status = Teacher::where('id', $user->teacher_id)->first()?->status;
        } elseif ($user->student_id !== null) {
            $status = Student::where('id', $user->student_id)->first()?->status;
        }
        $token = JWTAuth::fromUser($user);
        return response()->json([
            'message' => 'Login successful!',
            'user' => $user,
            'token' => $token,
            'status' => $status,
        ], 200);
    }


    public function create_admin()
    {
        $existingAdmin = User::where("role", "admin")->first();

        if ($existingAdmin) {
            return response()->json([
                'message' => 'Admin already exists!',
                'user' => $existingAdmin
            ], 200);
        }

        $newAdmin = User::create([
            'name' => "admin",
            'email' => "admin@gmail.com",
            'password' => Hash::make("123456"),
            'role' => "admin"
        ]);

        return response()->json([
            'message' => 'Admin created successfully!',
            'user' => $newAdmin
        ], 201);
    }
    public function signup(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|unique:users,email',
            'password' => 'required|string|min:6',
            'userType' => 'required|string|max:255'
        ]);

        $isUserExist = User::where("email", $request->email)->first();
        echo ($request->name);

        if ($isUserExist) {
            return response()->json([
                'message' => 'User already exists!',
                'user' => $isUserExist
            ], 200);
        } else {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'user_type' => $request->userType,
            ]);

            return response()->json([
                'message' => 'User registered successfully!',
                'user' => $user
            ], 201);
        }

    }

    public function get_user(Request $request)
    {
        $token = $request->header('token');
        if (!$token) {
            return response()->json(['error' => 'Token not provided'], 401);
        }
        try {
            $payload = JWTAuth::setToken($token)->getPayload();
            $userId = $payload->get('sub');
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid token'], 401);
        }
        $user = User::find($userId);
        return response()->json([
            'message' => 'User found successfully!',
            'user' => $user
        ], 201);
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $isEmailExist = User::where("email", $request->email)->first();
        if (!$isEmailExist) {
            return response()->json([
                'message' => 'Email not found!',
            ], 404);
        }
        $email = $request->email;
        $token = Str::random(60);
        PasswordReset::create([
            'email' => $email,
            'token' => $token,
        ]);
        Mail::raw("Click the link below to reset password:
        http://localhost:5173/reset-password/$token/$request->email", function ($message) use ($email) {
            $message->to($email)->subject('Password Reset Link');
        });
        return response()->json([
            'message' => 'Password reset link sent!',
        ], 200);
    }

    public function reset(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'token' => 'required|string',
            'password' => 'required|string|min:6',
        ]);
        $passwordReset = PasswordReset::where('email', $request->email)->where('token', $request->token)->first();
        if (!$passwordReset) {
            return response()->json([
                'message' => 'Invalid token or email!',
            ], 400);
        }
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User not found!',
            ], 404);
        }
        $user->password = Hash::make($request->password);
        $user->save();
        PasswordReset::where('email', $request->email)->delete();
        return response()->json([
            'message' => 'Password reset successfully!',
        ], 200);
    }

}
