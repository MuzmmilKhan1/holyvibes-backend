<?php

namespace App\Http\Controllers;

use App\Models\TeacherClassTimings;
use Illuminate\Http\Request;

class TeacherClassTimingsController extends Controller
{
    public function get_teacher_class_time($teacherID)
    {
        $class_time = TeacherClassTimings::with(['course'])->where('teacherID', $teacherID)->get();
        if (!$class_time) {
            return response()->json([
                'message' => 'No class time found for this teacher.',
            ], 404);
        }
        return response()->json([
            'message' => 'Teacher clases time found successfully.',
            'classTime' => $class_time
        ], 200);
    }

    public function add_edit_time(Request $request)
    {
        $validatedData = $request->validate([
            "classTimingID" => "required|integer",
            "courseID" => "required|integer|exists:courses,id",
            "preferred_time_from" => "required",
            "preferred_time_to" => "required",
            "teacherID" => "required|integer|exists:teachers,id",
        ]);
        if ($validatedData['classTimingID'] == 0) {
            $newTime = TeacherClassTimings::create([
                'courseID' => $validatedData['courseID'],
                'preferred_time_from' => $validatedData['preferred_time_from'],
                'preferred_time_to' => $validatedData['preferred_time_to'],
                'teacherID' => $validatedData['teacherID'],
            ]);
            return response()->json([
                'message' => 'Class timing created successfully.',
                'classTime' => $newTime,
            ], 201);
        } else {
            $existingTime = TeacherClassTimings::find($validatedData['classTimingID']);
            if (!$existingTime) {
                return response()->json([
                    'message' => 'Class timing not found.',
                ], 404);
            }
            $existingTime->update([
                'courseID' => $validatedData['courseID'],
                'preferred_time_from' => $validatedData['preferred_time_from'],
                'preferred_time_to' => $validatedData['preferred_time_to'],
                'teacherID' => $validatedData['teacherID'],
            ]);
            return response()->json([
                'message' => 'Class timing updated successfully.',
                'classTime' => $existingTime->fresh(),
            ], 200);
        }
    }

    public function delete_class_time($classTimingID)
    {
        $class_time = TeacherClassTimings::find($classTimingID);

        if (!$class_time) {
            return response()->json([
                'message' => 'No class time found.',
            ], 404);
        }

        $class_time->delete();

        return response()->json([
            'message' => 'Class time deleted successfully.',
            'classTime' => $class_time
        ], 200);
    }

}
