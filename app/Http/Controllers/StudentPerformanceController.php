<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentPerformance;

class StudentPerformanceController extends Controller
{
    public function add_performance(Request $request)
    {
        $teacherID = $request->get('user')->teacher_id;
        $request->validate([
            'classId' => 'required|exists:classes,id',
            'courseId' => 'required|exists:courses,id',
            'studentId' => 'required|exists:students,id',
            'attendance' => 'nullable|string',
            'testRemarks' => 'nullable|string',
            'classParticipation' => 'nullable|string',
            'suggestions' => 'nullable|string',
        ]);
        $exists = StudentPerformance::where('courseID', $request->courseId)
            ->where('studentID', $request->studentId)
            ->where('teacherID', $teacherID)
            ->where('classId', $request->classId)
            ->exists();
        if ($exists) {
            return response()->json([
                'message' => 'Performance record already exists for this student.'
            ], 409);
        }

        $performance = StudentPerformance::create([
            'classID' => $request->classId,
            'courseID' => $request->courseId,
            'studentID' => $request->studentId,
            'teacherID' => $teacherID,
            'attendance' => $request->attendance,
            'test_remarks' => $request->testRemarks,
            'participation' => $request->classParticipation,
            'suggestions' => $request->suggestions,
        ]);


        return response()->json([
            'message' => 'Performance record added successfully.',
            'data' => $performance
        ], 201); 
    }

    public function get_performance(Request $request)
    {
        $stdPerformance = StudentPerformance::with(['course', 'teacher', 'student','class'])->get();
        if ($stdPerformance->isEmpty()) {
            return response()->json([
                'message' => 'No performance records found.',
                'data' => [],
            ], 404);
        }
        return response()->json([
            'message' => 'All student performance records fetched successfully.',
            'data' => $stdPerformance,
        ], 200);
    }


}
