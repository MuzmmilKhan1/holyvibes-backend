<?php

namespace App\Http\Controllers;

use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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
            "qualification" => "required|string",
            "institution" => "required|string",
            "application_date" => "required|string|date",
            "course" => "required|array",
            "course.*.id" => "required",
            "course.*.name" => "required|string",
            "course.*.timings" => "required|array|min:1",
            "course.*.timings.*.from" => "required|string|date_format:H:i",
            "course.*.timings.*.to" => "required|string|date_format:H:i",
        ]);

        $courses = $validatedData['course'];
        unset($validatedData['course']);

        $newTeacher = Teacher::create($validatedData);

        foreach ($courses as $course) {
            $findCourse = Course::where('id', $course['id'])->first();
            $findCourse->teacherID = $newTeacher->id;
            $findCourse->save();
            $id = $findCourse->id;
            foreach ($course['timings'] as $time) {
                ClassTimings::create([
                    'courseID' => $id,
                    'teacherID' => $newTeacher->id,
                    'preferred_time_from' => $time['from'],
                    'preferred_time_to' => $time['to'],
                ]);
            }
        }

        return response()->json([
            'message' => 'Your application has been submitted successfully and is pending admin approval.',
            'teacher' => $newTeacher,
            'courses' => $findCourse,
        ], 201);
    }


    public function get_teachers()
    {
        $teachers = Teacher::with(['classTimings.course'])->get();
        return response()->json([
            'message' => 'Teachers with class timings and courses found successfully.',
            'teachers' => $teachers,
        ], 200);
    }


    public function assign_login_credentials(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required',
            'name' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        $teacher = Teacher::find($validatedData['teacherID']);
        if ($teacher) {
            $teacher->status = 'allowed';
            $teacher->name = $validatedData['name'];
            $teacher->email = $validatedData['email'];
            $teacher->save();
            $newUser = User::create([
                'teacher_id' => $validatedData['teacherID'],
                'name' => $validatedData["name"],
                'email' => $validatedData["email"],
                'password' => Hash::make($validatedData["password"]),
                'role' => 'teacher',
            ]);
            return response()->json([
                'message' => 'Teacher login credentials created succesfully',
                'user' => $newUser,
            ], 200);
        } else {
            return response()->json([
                'message' => '',
            ], 404);
        }
    }

    public function delete_teacher(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required|exists:teachers,id',
        ]);
        $teacherID = $validatedData['teacherID'];
        $teacher = Teacher::find($teacherID);
        $user = User::find($teacherID);
        if ($teacher) {
            $teacher->classTimings()->delete();
            Course::where('teacherID', $teacherID)->update(['teacherID' => null]);
            $teacher->delete();
        }
        if ($user) {
            $user->delete();
        }
        return response()->json([
            'message' => 'Teacher deleted successfully.',
        ], 200);
    }

    public function block_or_unblock_teacher(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required|exists:teachers,id',
        ]);
    
        $teacher = Teacher::find($validatedData['teacherID']);
        $currentStatus = "";
        if ($teacher->status === 'allowed') {
            $teacher->status = 'blocked';
            $currentStatus = 'blocked'; 
        } else {
            $teacher->status = 'allowed';
            $currentStatus = 'allowed'; 
        }
    
        $teacher->save();
    
        return response()->json([
            'message' => "Teacher {$currentStatus} successfully.",
            'status' => $teacher->status,
        ], 200);
    }
    
}