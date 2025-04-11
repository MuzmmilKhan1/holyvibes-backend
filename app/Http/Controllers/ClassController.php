<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\ClassTimings;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClassController extends Controller
{

    public function create_class(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'selectedCourseId' => 'required|integer',
            'selectedTimingID' => 'required|integer',
        ]);
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
        if (!$user || !$user->teacher_id) {
            return response()->json(['error' => 'Unauthorized or invalid teacher'], 403);
        }
        $teacherId = $user->teacher_id;
        $class = ClassModel::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'courseID' => $validatedData['selectedCourseId'],
            'teacherID' => $teacherId,
        ]);
        $timing = ClassTimings::find($validatedData['selectedTimingID']);
        if ($timing) {
            $timing->classID = $class->id;
            $timing->save();
        } else {
            return response()->json(['error' => 'Invalid timing ID'], 404);
        }
        return response()->json([
            'message' => 'Class created successfully!',
            'data' => $class,
            'classTimingID' => $timing->id,
        ], 201);
    }


    public function get_teacher_classes(Request $request)
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
        if (!$user || !$user->teacher_id) {
            return response()->json(['error' => 'Unauthorized or invalid teacher'], 403);
        }
        $teacherId = $user->teacher_id;
        $classes = ClassModel::where('teacherID', $teacherId)->get();
        return response()->json([
            'message' => 'Classes found successfully!',
            'data' => $classes,
        ], 201);
    }

}