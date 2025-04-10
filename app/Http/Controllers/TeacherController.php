<?php

namespace App\Http\Controllers;

use App\Models\ClassTimings;
use App\Models\Teacher;
use Illuminate\Http\Request;

class TeacherController extends Controller
{
    public function handle_requested_teacher(Request $request)
    {
        $validatedData = $request->validate([
            "name" => "required|string",
            "date_of_birth" => "required|string",
            "gender" => "required|string",
            "nationality" => "required|string",
            "contact_number" => "required|string",
            "email" => "required|string|email",
            "current_address" => "required|string",
            "experience_Quran" => "required|string",
            "other_experience" => "required|string",
            "languages_spoken" => "required|string",
            "age_group" => "required|string",
            "preferred_times" => "required|array",
            "preferred_times.*.from" => "required|string|date_format:H:i",
            "preferred_times.*.to" => "required|string|date_format:H:i",
            "qualification" => "required|string",
            "institution" => "required|string",
            "application_date" => "required|string|date",
        ]);

        $preferredTimes = $validatedData['preferred_times'];
        unset($validatedData['preferred_times']);
        $newTeacher = Teacher::create($validatedData);
        foreach ($preferredTimes as $time) {
            ClassTimings::create([
                'teacherID' => $newTeacher->id,
                'classID' => null,
                'preferred_time_from' => $time['from'],
                'preferred_time_to' => $time['to'],
            ]);
        }
        return response()->json([
            'message' => 'Your application has been submitted successfully and is pending admin approval.',
            'teacher' => $newTeacher,
        ], 201);
    }


    public function get_pending_teachers()
    {
        $teachers = Teacher::where('status', 'pending')->get();
        return response()->json([
            'message' => 'Teachers found successfully.',
            'teachers' => $teachers,
        ], 200);
    }

}