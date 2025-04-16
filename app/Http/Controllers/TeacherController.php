<?php

namespace App\Http\Controllers;

use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

class TeacherController extends Controller
{
    public function handle_requested_teacher(Request $request)
    {
        $validatedData = $request->validate([
            "name" => "required|string",
            "date_of_birth" => "required|date",
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
            "application_date" => "required|date",
            "course" => "required|array",
            "course.*.id" => "required|integer",
            "course.*.name" => "required|string",
            "course.*.timings" => "required|array|min:1",
            "course.*.timings.*.from" => "required|date_format:H:i",
            "course.*.timings.*.to" => "required|date_format:H:i",
        ]);

        $classTimings = [];
        foreach ($validatedData['course'] as $course) {
            foreach ($course['timings'] as $timing) {
                $classTimings[] = [
                    'course_id' => $course['id'],
                    'course_name' => $course['name'],
                    'from' => $timing['from'],
                    'to' => $timing['to'],
                ];
            }
        }
        unset($validatedData['course']);
        $validatedData['class_timings'] = json_encode($classTimings);
        $newTeacher = Teacher::create($validatedData);
        return response()->json([
            'message' => 'Your application has been submitted successfully and is pending admin approval.',
            'teacher' => $newTeacher,
        ], 201);
    }



    public function get_teachers()
    {
        $teachers = Teacher::all();
        return response()->json([
            'message' => 'Teachers with class timings and courses found successfully.',
            'teachers' => $teachers,
        ], 200);
    }

    public function assign_login_credentials(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required|integer',
            'name' => 'required|string',
            'email' => 'required|string|email',
            'password' => 'required|string|min:6',
            'courseIds' => 'required|array',
            'courseIds.*.course_id' => 'required|integer|exists:courses,id',
            'courseIds.*.course_name' => 'required|string',
            'courseIds.*.from' => 'required|date_format:H:i',
            'courseIds.*.to' => 'required|date_format:H:i',
        ]);


        // Find teacher
        $teacher = Teacher::find($validatedData['teacherID']);
        if (!$teacher) {
            return response()->json([
                'message' => 'Teacher not found',
            ], 404);
        }

        // Update teacher info
        $teacher->update([
            'teach_id' => $validatedData['teacherID'],
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'status' => 'allowed',
        ]);

        // Create user account
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make($validatedData['password']),
            'role' => 'teacher',
            'teacher_id' => $teacher->id,
        ]);

        // Assign teacher to courses
        foreach ($validatedData['courseIds'] as $course) {
            CourseTeacher::firstOrCreate([
                'teacherID' => $teacher->id,
                'courseID' => $course['course_id'],
            ]);
        }

        return response()->json([
            'message' => 'Login credentials assigned successfully.',
        ], 200);
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

    public function get_teacher_course(Request $request)
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
        $courses = CourseTeacher::with('course')->where('teacherID', $user->teacher_id)->get();
        return response()->json([
            'message' => 'Course found successfully.',
            'course' => $courses
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