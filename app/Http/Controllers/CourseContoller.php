<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\FileServices;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class CourseContoller extends Controller
{
    public function create_course(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'courseDuration' => 'required|string|max:100',
                'image' => 'required|image|mimes:jpeg,png,jpg|max:5120', // 5MB
            ]);
            $imageFile = $request->file('image');
            $imageContent = file_get_contents($imageFile->getRealPath());
            $imageBase64 = base64_encode($imageContent);
            $mimeType = $imageFile->getMimeType();
            $course = Course::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'price' => $validatedData['price'],
                'course_duration' => $validatedData['courseDuration'],
                'image' => "data:$mimeType;base64,$imageBase64",
            ]);
            return response()->json([
                'message' => 'Course created successfully!',
                'course_id' => $course->id,
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while creating the course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function get()
    {
        $courses = Course::all();
        return response()->json([
            'message' => 'Courses found successfully!',
            'course' => $courses,
        ], 201);
    }

    public function get_teacher_courses_time(Request $request)
    {
        $token = $request->header('token');
        // if (!$token) {
        //     return response()->json(['error' => 'Token not provided'], 401);
        // }
        // $payload = JWTAuth::setToken($token)->getPayload();
        // $userId = $payload->get('sub');
        try {
            // $courses = Course::where('teacherID', $userId)->get();
            return response()->json([
                'message' => 'Courses found successfully!',
                'token' => $token,
            ], 200);

        } catch (JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        }
    }



}
