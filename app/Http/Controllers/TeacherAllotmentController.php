<?php

namespace App\Http\Controllers;

use App\Models\CourseTeacher;
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
            $isCourseAssign = CourseTeacher::where('teacherID', $validated['teacherId'])
                ->where('courseID', $validated['courseId'])
                ->first();
            if (!$isCourseAssign) {
                return response()->json([
                    'error' => 'Teacher is not assigned to this course.'
                ], 400);
            }
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
            $user = $request->get('user');
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

    public function update_allotment(Request $request, $allotmentID)
    {
        try {
            $validatedData = $request->validate([
                'studentId' => 'required|exists:students,id',
                'teacherId' => 'required|exists:teachers,id',
                'courseId' => 'required|exists:courses,id',
                'classTimes' => 'required|array|min:1',
                'classTimes.*.id' => 'nullable|exists:student_class_timings,id',
                'classTimes.*.from' => 'required|string',
                'classTimes.*.to' => 'required|string',
            ]);
            $allotment = TeacherAllotment::findOrFail($allotmentID);
            $allotment->update([
                'studentID' => $validatedData['studentId'],
                'teacherID' => $validatedData['teacherId'],
                'courseID' => $validatedData['courseId'],
            ]);
            foreach ($validatedData['classTimes'] as $timing) {
                $stdClassTime = StudentClassTimings::findOrFail($timing['id']);
                $stdClassTime->preferred_time_from = $timing['from'];
                $stdClassTime->preferred_time_to = $timing['to'];
                $stdClassTime->save();
            }
            return response()->json([
                'message' => 'Teacher allotment updated successfully',
                'allotment' => $allotment

            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Teacher allotment not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the teacher allotment',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_allotment($allotmentID)
    {
        $allotment = TeacherAllotment::findOrFail($allotmentID);
        if (!$allotment) {
            return response()->json([
                'message' => 'Teacher allotment not found',
            ], 404);
        }
        $allotment->delete();
        return response()->json([
            'message' => 'Teacher allotment deleted',
        ], 200);
    }
}


