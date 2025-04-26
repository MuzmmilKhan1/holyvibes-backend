<?php

namespace App\Http\Controllers;

use App\Models\Student;
use Illuminate\Http\Request;
use App\Models\StudentPerformance;

class StudentPerformanceController extends Controller
{
    public function add_edit_performance(Request $request)
    {
        $teacherID = $request->get('user')->teacher_id;

        $request->validate([
            'id' => 'required|numeric',
            'classId' => 'required|exists:classes,id',
            'courseId' => 'required|exists:courses,id',
            'studentId' => 'required|exists:students,id',
            'attendance' => 'nullable|string',
            'testRemarks' => 'nullable|string',
            'classParticipation' => 'nullable|string',
            'suggestions' => 'nullable|string',
        ]);
        if ($request->id == 0) {
            $exists = StudentPerformance::where('courseID', $request->courseId)
                ->where('studentID', $request->studentId)
                ->where('teacherID', $teacherID)
                ->where('classID', $request->classId)
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
        } else {
            $performance = StudentPerformance::where('id', $request->id)
                ->where('teacherID', $teacherID)
                ->first();
            if (!$performance) {
                return response()->json([
                    'message' => 'Performance record not found or you don\'t have permission to edit it.'
                ], 404);
            }
            $performance->update([
                'classID' => $request->classId,
                'courseID' => $request->courseId,
                'studentID' => $request->studentId,
                'attendance' => $request->attendance,
                'test_remarks' => $request->testRemarks,
                'participation' => $request->classParticipation,
                'suggestions' => $request->suggestions,
            ]);
            return response()->json([
                'message' => 'Performance record updated successfully.',
                'data' => $performance
            ], 200);
        }
    }


    public function edit_performance(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|numeric',
                'classId' => 'required|exists:classes,id',
                'courseId' => 'required|exists:courses,id',
                'studentId' => 'required|exists:students,id',
                'teacherId' => 'required|exists:teachers,id',
                'attendance' => 'nullable|string',
                'test_remarks' => 'nullable|string',
                'participation' => 'nullable|string',
                'suggestions' => 'nullable|string',
            ]);

            $performance = StudentPerformance::where('id', $request->id)
                ->first();
            if (!$performance) {
                return response()->json([
                    'message' => 'Performance record not found or you don\'t have permission to edit it.'
                ], 404);
            }
            $performance->update([
                'classID' => $request->classId,
                'courseID' => $request->courseId,
                'studentID' => $request->studentId,
                'teacherID' => $request->teacherId,
                'attendance' => $request->attendance,
                'test_remarks' => $request->test_remarks,
                'participation' => $request->participation,
                'suggestions' => $request->suggestions,
            ]);
            return response()->json([
                'message' => 'Performance record updated successfully.',
                'data' => $performance
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while updating the performance record.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_performance(Request $request)
    {
        $stdPerformance = StudentPerformance::with(['course', 'teacher', 'student', 'class'])->get();
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

    public function delete_performance($reportID)
    {
        $report = StudentPerformance::find($reportID);
        if (!$report) {
            return response()->json([
                'message' => 'Performance report not found!',
            ], 404);
        }
        $report->delete();
        return response()->json([
            'message' => 'Performance report deleted successfully!',
        ], 200);
    }


}
