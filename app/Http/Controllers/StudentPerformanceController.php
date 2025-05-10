<?php

namespace App\Http\Controllers;

use App\Mail\ClassMail;
use App\Mail\StudentReport;
use App\Models\ClassModel;
use App\Models\Course;
use App\Models\Student;
use App\Models\StudentClassTimings;
use Illuminate\Http\Request;
use App\Models\StudentPerformance;
use App\Models\Teacher;
use App\Models\TeacherAllotment;
use Illuminate\Support\Facades\Mail;

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
            $isStdEnrolledInCourse = TeacherAllotment::where('courseID', $request->courseId)
                ->where('studentID', $request->studentId)
                ->where('teacherID', $teacherID)
                ->exists();
            if (!$isStdEnrolledInCourse) {
                return response()->json([
                    'message' => 'Student is not enrolled in this course under this teacher.'
                ], 403);
            }
            $isStdEnrolledInClass = StudentClassTimings::where('classID', $request->classId)
                ->where('studentID', $request->studentId)
                ->exists();
            if (!$isStdEnrolledInClass) {
                return response()->json([
                    'message' => 'Student is not enrolled in this class under this teacher.'
                ], 403);
            }


            $student = Student::find($request->studentId);
            $course = Course::find($request->courseId);
            $class = ClassModel::find($request->classId);


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

            Mail::to($student->email)->send(new StudentReport($student, $course, $class, $performance));

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

            $student = Student::find($request->studentId);
            $teacher = Teacher::find($teacherID);

            if ($student) {
                $title = 'Performance Report Updated';
                $message = 'Your performance report has been updated';
                $body = '
                <p>Dear ' . $student->name . ',</p>
                <p>Your performance report has been updated by <strong>' . ($teacher ? $teacher->name : 'your instructor') . '</strong>.</p>
                <p><strong>Attendance:</strong> ' . $request->attendance . '</p>
                <p><strong>Test Remarks:</strong> ' . $request->testRemarks . '</p>
                <p><strong>Participation:</strong> ' . $request->classParticipation . '</p>
                <p><strong>Suggestions:</strong> ' . $request->suggestions . '</p>
                <br>
                <p>The report has been updated successfully. Please review the new details in your dashboard.</p>
                <br>
                <p>Thanks,<br>The HolyVibes Team</p>
            ';
                Mail::to($student->email)->send(new ClassMail($title, $message, $body));
            }

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
                'courseId' => 'required:exists:courses,id',
                'studentId' => 'required|exists:students,id',
                'teacherId' => 'required|exists:teachers,id',
                'attendance' => 'nullable|string',
                'test_remarks' => 'nullable|string',
                'participation' => 'nullable|string',
                'suggestions' => 'nullable|string',
            ]);

            $performance = StudentPerformance::where('id', $request->id)->first();
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
            $student = Student::find($request->studentId);
            $teacher = Teacher::find($request->teacherId);
            $title = 'Performance Report Updated';
            $message = 'Your performance report has been updated';
            $body = '
                <p>Dear ' . $student->name . ',</p>
                <p>Your performance report has been updated by <strong>' . ($teacher ? $teacher->name : 'your instructor') . '</strong>.</p>
                <p><strong>Attendance:</strong> ' . $request->attendance . '</p>
                <p><strong>Test Remarks:</strong> ' . $request->test_remarks . '</p>
                <p><strong>Participation:</strong> ' . $request->participation . '</p>
                <p><strong>Suggestions:</strong> ' . $request->suggestions . '</p>
                <br>
                <p>The report has been updated successfully. Please review the new details in your dashboard.</p>
                <br>
                <p>Thanks,<br>The HolyVibes Team</p>
            ';
            Mail::to($student->email)->send(new ClassMail($title, $message, $body));

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
        $student = Student::find($report->studentID);
        $teacher = Teacher::find($report->teacherID);
        if ($student) {
            $title = 'Performance Report Deleted';
            $message = 'A performance report has been removed from your profile';
            $teacherName = $teacher ? $teacher->name : 'an instructor';
            $body = '
            <p>Dear ' . $student->name . ',</p>
            <p>This is to inform you that a performance report uploaded by <strong>' . $teacherName . '</strong> has been deleted from your records.</p>
            <p>If you believe this was a mistake or have any concerns, please reach out to your instructor or administrator.</p>
            <br>
            <p>Thanks,<br>The HolyVibes Team</p>
        ';
            Mail::to($student->email)->send(new ClassMail($title, $message, $body));
        }
        $report->delete();
        return response()->json([
            'message' => 'Performance report deleted successfully and student notified!',
        ], 200);
    }


}
