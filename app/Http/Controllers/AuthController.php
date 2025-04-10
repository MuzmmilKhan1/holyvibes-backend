<?php

namespace App\Http\Controllers;

use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();
        $status = "";
        if ($user->teacher_id) {
            $status = Teacher::where('id', $user->teacher_id)->first()?->status;
        } else if ($user->student_id) {
            // $status = Student::where('id', $user->student_id)->first()?->status;
            $status = 'skdksd';
        }
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Create JWT token
        $token = JWTAuth::fromUser($user);

        return response()->json([
            'message' => 'Login successful!',
            'user' => $user,
            'token' => $token,
            'status' => $status
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



}
