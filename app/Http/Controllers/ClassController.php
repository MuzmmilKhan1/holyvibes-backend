<?php

namespace App\Http\Controllers;

use App\Models\ClassCourse;
use App\Models\ClassModel;
use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\StudentClassTimings;
use App\Models\TeacherAllotment;
use App\Models\TeacherClassTimings;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\DB;

class ClassController extends Controller
{


    public function create_class(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'link' => 'required|string|max:255',
            'courseIds' => 'required|array|min:1',
            'courseIds.*' => 'required|integer|exists:courses,id',
            'classTime.from' => 'required|date_format:H:i',
            'classTime.to' => 'required|date_format:H:i|after:classTime.from',
        ]);

        $user = $request->get('user');
        if (!$user || !$user->teacher_id) {
            return response()->json(['error' => 'Unauthorized or invalid teacher'], 401);
        }
        $teacherId = $user->teacher_id;

        DB::beginTransaction();

        try {
            $class = ClassModel::create([
                'title' => $validatedData['title'],
                'classLink' => $validatedData['link'],
                'teacherID' => $teacherId,
            ]);
            foreach ($validatedData['courseIds'] as $courseId) {
                ClassCourse::create([
                    'classID' => $class->id,
                    'courseID' => $courseId,
                ]);
            }
            $matchingStudents = StudentClassTimings::whereIn('courseID', $validatedData['courseIds'])
                ->where('preferred_time_from', $validatedData['classTime']['from'])
                ->where('preferred_time_to', $validatedData['classTime']['to'])
                ->whereHas('teacherAllotment', function ($query) use ($teacherId, $validatedData) {
                    $query->where('teacherID', $teacherId)
                        ->whereIn('courseID', $validatedData['courseIds']);
                })
                ->get();
            $updatedCount = 0;
            foreach ($matchingStudents as $studentTiming) {
                $studentTiming->update([
                    'classID' => $class->id,
                ]);
                $updatedCount++;
            }
            foreach ($validatedData['courseIds'] as $courseId) {
                TeacherClassTimings::create([
                    'classID' => $class->id,
                    'teacherID' => $teacherId, // Fixed typo: taecherID -> teacherID
                    'courseID' => $courseId,
                    'preferred_time_from' => $validatedData['classTime']['from'],
                    'preferred_time_to' => $validatedData['classTime']['to'],
                ]);
            }
            DB::commit();
            return response()->json([
                'message' => 'Class created and students updated successfully!',
                'classId' => $class->id,
                'updatedStudentsCount' => $updatedCount,
                'assignedCoursesCount' => count($validatedData['courseIds']),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong.',
                'details' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    public function get_teacher_classes(Request $request)
    {
        try {
            $user = $request->get('user');
            if (!$user || !$user->teacher_id) {
                return response()->json([
                    'message' => 'Unauthorized or invalid teacher',
                ], 401);
            }
            $teacherId = $user->teacher_id;
            $classes = ClassModel::with([
                'courses' => function ($query) {
                    $query->select('courses.id', 'courses.name');
                },
                'teacherClassTimings' => function ($query) {
                    $query->select(
                        'teacher_class_timings.id',
                        'teacher_class_timings.classID',
                        'teacher_class_timings.courseID',
                        'teacher_class_timings.preferred_time_from',
                        'teacher_class_timings.preferred_time_to'
                    );
                }
            ])
                ->where('teacherID', $teacherId)
                ->select('id', 'title', 'classLink', 'teacherID')
                ->get();
            $classes = $classes->map(function ($class) {
                return [
                    'id' => $class->id,
                    'title' => $class->title,
                    'classLink' => $class->classLink,
                    'teacherID' => $class->teacherID,
                    'courses' => $class->courses->map(function ($course) {
                        return [
                            'id' => $course->id,
                            'name' => $course->name,
                        ];
                    }),
                    'timings' => $class->teacherClassTimings->map(function ($timing) {
                        return [
                            'id' => $timing->id,
                            'courseID' => $timing->courseID,
                            'from' => $timing->preferred_time_from,
                            'to' => $timing->preferred_time_to,
                        ];
                    }),
                ];
            });
            return response()->json([
                'message' => 'Classes found successfully!',
                'data' => $classes,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching classes',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    public function get_all()
    {
        $classes = ClassModel::with('teacherClassTimings')->get();
        return response()->json([
            'message' => 'Classes found successfully!',
            'data' => $classes,
        ], 201);
    }

    
  


   

    public function edit_class(Request $request, $classID)
    {
        try {
            $validated = $request->validate([
                'title' => 'required|string|max:255',
                'link' => 'required|string|max:255',
                'classTime' => 'required|array',
                'classTime.id' => 'required|integer|exists:teacher_class_timings,id',
                'classTime.from' => 'required|',
                'classTime.to' => 'required|',
            ]);
            $class = ClassModel::where('id', $classID)->first();
            if (!$class) {
                return response()->json(['error' => 'Class not found'], 404);
            }
            DB::beginTransaction();
            try {
                $class->title = $validated['title'];
                $class->classLink = $validated['link'];
                $class->save();
                $timing = TeacherClassTimings::where('id', $validated['classTime']['id'])->where('classID', $classID)->first();
                if (!$timing) {
                    DB::rollBack();
                    return response()->json(['error' => 'Timing record not found'], 404);
                }
                $timing->preferred_time_from = $validated['classTime']['from'];
                $timing->preferred_time_to = $validated['classTime']['to'];
                $timing->save();
                // StudentClassTimings::where('classID', $classID)
                //     ->update([
                //         'preferred_time_from' => $validated['classTime']['from'],
                //         'preferred_time_to' => $validated['classTime']['to'],
                //     ]);
                DB::commit();
                return response()->json([
                    'message' => 'Class updated successfully!',
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'error' => 'Failed to update class',
                    'details' => $e->getMessage(),
                    'trace' => config('app.debug') ? $e->getTrace() : null,
                ], 500);
            }

        } catch (JWTException $e) {
            return response()->json([
                'error' => 'Invalid or expired token',
                'details' => $e->getMessage(),
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'An error occurred',
                'details' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    public function assign_class(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'classId' => 'required|exists:classes,id',
                'students' => 'required|array|min:1',
                'students.*.studentId' => 'required|exists:students,id',
                'students.*.classTimings' => 'required|array|min:1',
                'students.*.classTimings.*.id' => 'required|exists:student_class_timings,id',
                'students.*.classTimings.*.preferred_time_from' => 'required|string',
                'students.*.classTimings.*.preferred_time_to' => 'required|string',
            ]);

            $updatedCount = 0;

            foreach ($validatedData['students'] as $student) {
                $studentId = $student['studentId'];
                $timingIds = array_column($student['classTimings'], 'id');

                $updated = StudentClassTimings::where('studentID', $studentId)
                    ->whereIn('id', $timingIds)
                    ->update(['classID' => $validatedData['classId']]);

                $updatedCount += $updated;
            }

            if ($updatedCount === 0) {
                return response()->json([
                    'message' => 'No matching class timings found for the given students or no updates needed'
                ], 404);
            }

            return response()->json([
                'message' => 'Students assigned to class successfully',
                'updated_count' => $updatedCount
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while assigning students to class',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function get_students(Request $request)
    {
        try {
            $teacherID = $request->get('user')->teacher_id;
            $classes = ClassModel::where('teacherID', $teacherID)
                ->pluck('id');
            $students = StudentClassTimings::with('student', 'class')
                ->whereIn('classID', $classes)
                ->get();
            return response()->json([
                'message' => 'Students retrieved successfully',
                'students' => $students
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while retrieving students',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function remove_students($classID, $studentID, $stdClassTimingID)
    {
        try {
            $stdClassTime = StudentClassTimings::where('studentID', $studentID)
                ->where('classID', $classID)
                ->where('id', $stdClassTimingID)
                ->first();

            if (!$stdClassTime) {
                return response()->json([
                    'message' => 'Class timing not found for the specified student and class'
                ], 404);
            }

            $stdClassTime->classID = null;
            $stdClassTime->save();

            return response()->json([
                'message' => 'Student removed from class successfully'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while removing the student from the class',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function delete_class($classID)
    {
        try {
            $class = ClassModel::find($classID);
            if (!$class) {
                return response()->json([
                    'message' => 'Class not found'
                ], 404);
            }
            $updatedCount = StudentClassTimings::where('classID', $classID)
                ->update(['classID' => null]);
            $class->delete();
            return response()->json([
                'message' => 'Class deleted successfully',
                'unassigned_timings_count' => $updatedCount
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while deleting the class',
                'error' => $e->getMessage(),
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