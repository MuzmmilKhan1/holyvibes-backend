<?php

namespace App\Http\Controllers;

use App\Mail\ClassMail;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Student;
use App\Models\StudentClassTimings;
use App\Models\Teacher;
use App\Models\TeacherAllotment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
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

            $existingAllotment = TeacherAllotment::firstOrCreate([
                'teacherID' => $validated['teacherId'],
                'studentID' => $validated['studentId'],
                'courseID' => $validated['courseId'],
            ]);

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

            $teacher = Teacher::find($validated['teacherId']);
            $student = Student::find($validated['studentId']);
            $course = Course::find($validated['courseId']);

            $timeSlots = collect($validated['classTimes'])
                ->map(fn($t) => $t['from'] . ' - ' . $t['to'])
                ->implode('<br>');


            $title = 'New Student Allotted for Course';
            $subtitle = 'Student: ' . $student->name;
            $body = 'Dear ' . $teacher->name . ',<br><br>' .
                'You have been allotted to a student for the course "<strong>' . $course->name . '</strong>".<br>' .
                'Student: <strong>' . $student->name . '</strong><br>' .
                'Scheduled Class Timings:<br>' . $timeSlots . '<br><br>' .
                'Thank you,<br>The HolyVibes Team';
            Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));

            $title = 'Teacher Assigned for Your Course';
            $subtitle = 'Course: ' . $course->name;
            $body = 'Dear ' . $student->name . ',<br><br>' .
                'You have been assigned a teacher for your course "<strong>' . $course->name . '</strong>".<br>' .
                'Teacher: <strong>' . $teacher->name . '</strong><br>' .
                'Scheduled Class Timings:<br>' . $timeSlots . '<br><br>' .
                'Thank you,<br>The HolyVibes Team';
            Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));

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

            $timeSlots = '';
            foreach ($validatedData['classTimes'] as $timing) {
                $stdClassTime = StudentClassTimings::findOrFail($timing['id']);
                $stdClassTime->preferred_time_from = $timing['from'];
                $stdClassTime->preferred_time_to = $timing['to'];
                $stdClassTime->save();

                $timeSlots .= 'From: ' . $timing['from'] . ' To: ' . $timing['to'] . '<br>';
            }

            // Send notifications
            $student = Student::findOrFail($validatedData['studentId']);
            $teacher = Teacher::findOrFail($validatedData['teacherId']);
            $course = Course::findOrFail($validatedData['courseId']);

            // Notify teacher
            $title = 'Allotment Updated for Your Course';
            $subtitle = 'Updated Teacher Assignment: ' . $teacher->name;
            $body = 'Dear ' . $student->name . ',<br><br>' .
                'Your teacher allotment for the course "<strong>' . $course->name . '</strong>" has been <strong>updated</strong>.<br>' .
                'Teacher: <strong>' . $teacher->name . '</strong><br>' .
                'Updated Class Timings:<br>' . $timeSlots . '<br><br>' .
                'Thank you,<br>The HolyVibes Team';
            Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));

            // Notify student
            $title = 'Allotment Updated for Student';
            $subtitle = 'Updated Student Allotment: ' . $student->name;
            $body = 'Dear ' . $teacher->name . ',<br><br>' .
                'The allotment details for your student in the course "<strong>' . $course->name . '</strong>" have been <strong>updated</strong>.<br>' .
                'Student: <strong>' . $student->name . '</strong><br>' .
                'Updated Class Timings:<br>' . $timeSlots . '<br><br>' .
                'Thank you,<br>The HolyVibes Team';
            Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));


            return response()->json([
                'message' => 'Teacher allotment updated successfully and notifications sent',
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

        $student = Student::findOrFail($allotment->studentID);
        $teacher = Teacher::findOrFail($allotment->teacherID);
        $course = Course::findOrFail($allotment->courseID);

        $allotment->delete();

        $title = 'Allotment Removed';
        $subtitle = 'Course: ' . $course->name;

        $teacherBody = 'Dear ' . $teacher->name . ',<br><br>' .
            'The student allotment for the course "<strong>' . $course->name . '</strong>" has been <strong>removed</strong>.<br>' .
            'Student: <strong>' . $student->name . '</strong><br><br>' .
            'Thank you,<br>The HolyVibes Team';

        $studentBody = 'Dear ' . $student->name . ',<br><br>' .
            'Your teacher allotment for the course "<strong>' . $course->name . '</strong>" has been <strong>removed</strong>.<br>' .
            'Teacher: <strong>' . $teacher->name . '</strong><br><br>' .
            'Thank you,<br>The HolyVibes Team';

        Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $teacherBody));
        Mail::to($student->email)->send(new ClassMail($title, $subtitle, $studentBody));

        return response()->json([
            'message' => 'Teacher allotment deleted and notifications sent',
        ], 200);
    }

}
