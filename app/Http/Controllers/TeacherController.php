<?php

namespace App\Http\Controllers;

use App\Models\ClassTimings;
use App\Models\Course;
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
        $validatedData['class_course_schedule'] = json_encode($validatedData['course']);
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
            'teacherID' => 'required',
            'name' => 'required|string',
            'email' => 'required|string',
            'password' => 'required|string|min:6',
            'courseIds' => 'required|string',
        ]);
        $courseIds = json_decode($validatedData['courseIds'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json([
                'message' => 'Invalid course data format.',
            ], 400);
        }
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
            foreach ($courseIds as $course) {
                $courseModel = Course::find($course['id']);
                $courseModel->teacherID = $teacher->id;
                $courseModel->save();
                if ($courseModel) {
                    foreach ($course['timings'] as $timing) {
                        ClassTimings::updateOrCreate(
                            [
                                'teacherID' => $teacher->id,
                                'courseID' => $courseModel->id,
                                'preferred_time_from' => $timing['from'],
                                'preferred_time_to' => $timing['to'],
                            ],
                            [
                                'teacherID' => $teacher->id,
                                'courseID' => $courseModel->id,
                                'preferred_time_from' => $timing['from'],
                                'preferred_time_to' => $timing['to'],
                            ]
                        );
                    }
                }
            }
            return response()->json([
                'message' => 'Teacher login credentials created successfully',
                'user' => $newUser,
            ], 200);
        } else {
            return response()->json([
                'message' => 'Teacher not found',
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

        $courses = Course::where('teacherID', $user->teacher_id)->get();

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