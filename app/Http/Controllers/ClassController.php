<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class ClassController extends Controller
{

    public function create_class(Request $request)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'selectedCourseId' => 'required|integer',
            'selectedTimingID' => 'required|integer',
        ]);
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
        $teacherId = $user->teacher_id;
        $class = ClassModel::create([
            'title' => $validatedData['title'],
            'description' => $validatedData['description'],
            'courseID' => $validatedData['selectedCourseId'],
            'teacherID' => $teacherId,
            'classLink' => $request->link,
        ]);
        $timing = ClassTimings::find($validatedData['selectedTimingID']);
        if ($timing) {
            $timing->classID = $class->id;
            $timing->save();
        } else {
            return response()->json(['error' => 'Invalid timing ID'], 404);
        }
        return response()->json([
            'message' => 'Class created successfully!',
            'data' => $class,
            'classTimingID' => $timing->id,
        ], 201);
    }


    public function get_teacher_classes(Request $request)
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
        if (!$user || !$user->teacher_id) {
            return response()->json(['error' => 'Unauthorized or invalid teacher'], 403);
        }
        $teacherId = $user->teacher_id;
        $classes = ClassModel::with(['course'])->where('teacherID', $teacherId)->get();
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
                $class = ClassTimings::where('classID', $classID)->get();
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
            $classTiming = ClassTimings::find($validatedData['selectedTimingID']);
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

}