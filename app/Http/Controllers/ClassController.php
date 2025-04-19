<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\StudentClassTimings;
use App\Models\TeacherClassTimings;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{

    public function create_class(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'courseId' => 'required|integer|exists:courses,id',
            'classTime.from' => 'required|date_format:H:i',
            'classTime.to' => 'required|date_format:H:i',
        ]);
        $user = $request->get('user');
        $teacherId = $user->teacher_id;
        $courseId = $validatedData['courseId'];

        DB::beginTransaction();

        try {
            // Create the class
            $class = ClassModel::create([
                'title' => $validatedData['title'],
                'classLink' => $validatedData['link'],
                'courseID' => $courseId,
                'teacherID' => $teacherId,
            ]);

            // Find matching student_class_timings
            $matchingStudents = StudentClassTimings::where('courseID', $courseId)
                ->where('preferred_time_from', $validatedData['classTime']['from'])
                ->where('preferred_time_to', $validatedData['classTime']['to'])
                ->whereHas('teacherAllotment', function ($query) use ($teacherId, $courseId) {
                    $query->where('teacherID', $teacherId)
                        ->where('courseID', $courseId);
                })
                ->get();

            // Update classID for each matched student
            foreach ($matchingStudents as $studentTiming) {
                $studentTiming->classID = $class->id;
                $studentTiming->save();
            }

            TeacherClassTimings::create([
                'classID' => $class->id,
                'taecherID' => $teacherId,
                'courseID' => $courseId,
                'preferred_time_from' => $validatedData['classTime']['from'],
                'preferred_time_to' => $validatedData['classTime']['to'],
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Class created and students updated successfully!',
                'classId' => $class->id,
                'updatedStudentsCount' => $matchingStudents->count(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }


    public function get_teacher_classes(Request $request)
    {
        $user = $request->get('user');
        $teacherId = $user->teacher_id;
        $classes = ClassModel::with(['course:id,name', 'teacherClassTimings'])
            ->where('teacherID', $teacherId)
            ->get(['id', 'title', 'classLink', 'courseID', 'teacherID']);

        return response()->json([
            'message' => 'Classes found successfully!',
            'data' => $classes,
        ], 201);
    }

    public function get_all()
    {
        $classes = ClassModel::all();
        return response()->json([
            'message' => 'Classes found successfully!',
            'data' => $classes,
        ], 201);
    }

    public function get_class($courseID)
    {
        try {
            if ($courseID) {
                $class = ClassModel::where('courseID', $courseID)->get();
                if (!$class) {
                    return response()->json([
                        'message' => 'Class not found!',
                    ], 404);
                }

                return response()->json([
                    'message' => 'Classs fetched successfully!',
                    'class' => $class,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Class ID is required!',
                ], 400);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function get_class_time($classID)
    {
        try {
            if ($classID) {
                $class = TeacherClassTimings::where('classID', $classID)->get();
                if (!$class) {
                    return response()->json([
                        'message' => 'Class timing not found!',
                    ], 404);
                }
                return response()->json([
                    'message' => 'Class timings fetched successfully!',
                    'classTime' => $class,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Class ID is required!',
                ], 400);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }


    public function get_single_class_data($classID)
    {
        try {
            if ($classID) {
                $class = ClassModel::with(['classTimings', 'teacher', 'course.classTimings'])->where('id', $classID)->first();
                if (!$class) {
                    return response()->json([
                        'message' => 'Class not found!',
                    ], 404);
                }
                return response()->json([
                    'message' => 'Class details fetched successfully!',
                    'class' => $class,
                ], 200);
            } else {
                return response()->json([
                    'message' => 'Class ID is required!',
                ], 400);
            }
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function edit_class(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required',
            ]);
            $class = ClassModel::find($request->id);
            if (!$class) {
                return response()->json([
                    'message' => 'Class not found!',
                ], 404);
            }
            $class->title = $request->title;
            $class->description = $request->description;
            $class->classLink = $request->class_link;
            $class->total_seats = $request->total_seats;
            $class->filled_seats = $request->filled_seats;
            $class->save();
            return response()->json([
                'message' => 'Class updated successfully!',
                'class' => $class,
            ], 200);

        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['error' => 'Invalid or expired token'], 401);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

    public function edit_by_teacher_class(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|integer|exists:classes,id',
                'description' => 'required|string',
                'link' => 'required|string|url',
                'selectedTimingID' => 'required|integer|exists:class_timings,id',
                'title' => 'required|string',
            ]);
            $class = ClassModel::find($validatedData['id']);
            if (!$class) {
                return response()->json([
                    'message' => 'Class not found!',
                ], 404);
            }
            $class->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
                'classLink' => $validatedData['link'],
            ]);
            $classTiming = TeacherClassTimings::find($validatedData['selectedTimingID']);
            if (!$classTiming) {
                return response()->json([
                    'message' => 'Class timing not found!',
                ], 404);
            }
            $classTiming->classID = $class->id;
            $classTiming->save();
            return response()->json([
                'message' => 'Class and timing updated successfully!',
                'class' => $class->fresh(),
                'classTiming' => $classTiming,
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function get_class_students($classId)
    {
        try {
            $classStds = StudentClassTimings::with(['student'])->where('classID', $classId)->get()->pluck('student')
                ->filter();
            if (!$classStds) {
                return response()->json([
                    'message' => 'Class students not found!',
                ], 404);
            }
            return response()->json([
                'message' => 'Class students fetched successfully!',
                'students' => $classStds,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

}