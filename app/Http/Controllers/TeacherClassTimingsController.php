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
