<?php

namespace App\Http\Controllers;

use App\Models\ClassCourse;
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