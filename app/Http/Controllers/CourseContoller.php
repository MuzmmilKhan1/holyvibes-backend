<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Services\FileServices;
use Illuminate\Http\Request;

class CourseContoller extends Controller
{
    public function create_course(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|integer',
            'courseDuration' => 'required|string',
            'image' => 'required|image|max:5120',
        ]);


        $fileService = new FileServices();
        $path = $fileService->store_file($request->file('image'));

        $new_course = Course::create([
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'price' => $request->input('price'),
            'course_duration' => $request->input('courseDuration'),
            'image' => $path, 
        ]);

        return response()->json([
            'message' => 'Course created successfully!',
            'image_url' => asset('storage/' . $path), 
            'course' => $new_course,
        ], 201);
    }

    public function get()
    {
        $courses = Course::all();
        return response()->json([
            'message' => 'Courses found successfully!',
            'course' => $courses,
        ], 201);
    }

}
