<?php

namespace App\Http\Controllers;

use App\Models\StudentClassTimings;
use App\Models\TeacherAllotment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;

class TeacherAllotmentController extends Controller
{

    public function allot_teacher(Request $request)
    {
        $validated = $request->validate([
            'teacherId' => 'required|exists:teachers,id',
            'studentId' => 'required|exists:students,id',
            'courseId' => 'required|exists:courses,id',
            'classTimes' => 'required|array|min:1',
            'classTimes.*.from' => 'required|string',
            'classTimes.*.to' => 'required|string',
        ]);
        DB::beginTransaction();
        try {
            $existingAllotment = TeacherAllotment::where('teacherID', $validated['teacherId'])
                ->where('studentID', $validated['studentId'])
                ->where('courseID', $validated['courseId'])
                ->first();
            if (!$existingAllotment) {
                $existingAllotment = TeacherAllotment::create([
                    'teacherID' => $validated['teacherId'],
                    'studentID' => $validated['studentId'],
                    'courseID' => $validated['courseId'],
                ]);
            }
            foreach ($validated['classTimes'] as $timing) {
                $existingTiming = StudentClassTimings::where('studentID', $validated['studentId'])
                    ->where('preferred_time_from', $timing['from'])
                    ->where('preferred_time_to', $timing['to'])
                    ->first();
                if ($existingTiming) {
                    DB::rollBack();
                    return response()->json([
                        'message' => 'Student class timing already exists for time: ' . $timing['from'] . ' - ' . $timing['to'],
                    ], 422);
                }
                StudentClassTimings::create([
                    'allotmentID' => $existingAllotment->id,
                    'studentID' => $validated['studentId'],
                    'courseID' => $validated['courseId'],
                    'preferred_time_from' => $timing['from'],
                    'preferred_time_to' => $timing['to'],
                ]);
            }

            DB::commit();
            return response()->json([
                'message' => 'Teacher allotment and class timings updated successfully',
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to allot teacher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_allotment()
    {
        try {
            $teacherAllotment = TeacherAllotment::with(['course', 'student', 'teacher', 'studentClassTimings'])->get();

            return response()->json([
                'message' => 'Teacher allotment found successfully',
                'teacherAllotment' => $teacherAllotment,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get allotted teacher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_teacher_allotment(Request $request)
    {
        try {
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

            $teacherAllotment = TeacherAllotment::with(['student', 'course', 'teacher', 'studentClassTimings'])
                ->where('teacherID', $user->teacher_id)
                ->get();

            return response()->json([
                'message' => 'Teacher allotment found successfully',
                'teacherAllotment' => $teacherAllotment,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to get allotted teacher',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    // public function update_allotment(Request $request, $id)
    // {
    //     $request->validate([
    //         'studentId' => 'required|exists:students,id',
    //         'teacherId' => 'required|exists:teachers,id',
    //         'courseId' => 'required|exists:courses,id',
    //         'classTimes' => 'required|array',
    //         'classTimes.*.from' => 'required|string',
    //         'classTimes.*.to' => 'required|string',
    //     ]);

    //     $allotment = TeacherAllotment::findOrFail($id);

    //     $allotment->studentID = $request->studentId;
    //     $allotment->teacherID = $request->teacherId;
    //     $allotment->courseID = $request->courseId;
    //     $allotment->save();

    //     // First delete old class timings
    //     $allotment->classTimings()->delete();

    //     // Then insert updated class timings
    //     foreach ($request->classTimes as $time) {
    //         $allotment->classTimings()->create([
    //             'preferred_time_from' => $time['from'],
    //             'preferred_time_to' => $time['to'],
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Teacher allotment updated successfully',
    //         'allotment' => $allotment->load('classTimings', 'student', 'teacher', 'course')
    //     ]);
    // }




}


