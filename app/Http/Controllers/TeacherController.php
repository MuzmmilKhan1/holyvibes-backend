<?php

namespace App\Http\Controllers;

use App\Mail\ClassMail;
use App\Models\ClassCourse;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\StudentClassTimings;
use App\Models\StudentPerformance;
use App\Models\Teacher;
use App\Models\TeacherAllotment;
use App\Models\TeacherClassTimings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class TeacherController extends Controller
{
    public function handle_requested_teacher(Request $request)
    {
        $validatedData = $request->validate([
            "name" => "required|string",
            "date_of_birth" => "required|date",
            "gender" => "required|string",
            "nationality" => "required|string",
            "contact_number" => "required|string",
            "email" => "required|email|unique:students,email|unique:users,email|unique:teachers,email",
            "current_address" => "required|string",
            "experience_Quran" => "required|string",
            "other_experience" => "required|string",
            "languages_spoken" => "required|string",
            "age_group" => "required|string",
            "qualification" => "required|string",
            "institution" => "required|string",
            "application_date" => "required|date",
            "course" => "required|array",
            "course.*.id" => "required|integer",
            "course.*.name" => "required|string",
            "course.*.timings" => "required|array|min:1",
            "course.*.timings.*.from" => "required|date_format:H:i",
            "course.*.timings.*.to" => "required|date_format:H:i",
        ]);
        $admin = User::where("role", "admin")->first();
        $classTimings = [];
        foreach ($validatedData['course'] as $course) {
            foreach ($course['timings'] as $timing) {
                $classTimings[] = [
                    'course_id' => $course['id'],
                    'course_name' => $course['name'],
                    'from' => $timing['from'],
                    'to' => $timing['to'],
                ];
            }
        }
        $courses = $validatedData['course'];
        unset($validatedData['course']);
        $validatedData['class_timings'] = json_encode($classTimings);
        $newTeacher = Teacher::create($validatedData);
        $title = 'New Teacher Application Submitted';
        $subtitle = 'New Teacher Application Pending Admin Approval';
        $body = 'A new teacher has submitted an application for your approval. Below are the details:<br><br>
        <strong>Name:</strong> ' . $newTeacher->name . '<br>
        <strong>Email:</strong> ' . $newTeacher->email . '<br>
        <strong>Courses Requested:</strong><br>
        <ul>';
        foreach ($courses as $course) {
            foreach ($course['timings'] as $timing) {
                $body .= '<li>' . $course['name'] . ' (From: ' . $timing['from'] . ' - To: ' . $timing['to'] . ')</li>';
            }
        }
        $body .= '</ul><br><br>
        Please review the teacher’s application and proceed with the approval or rejection.<br><br>
        <p>Thanks,<br>The HolyVibes Team</p>';
        Mail::to($admin->email)->send(new ClassMail($title, $subtitle, $body));

        return response()->json([
            'message' => 'Your application has been submitted successfully and is pending admin approval.',
            'teacher' => $newTeacher,
        ], 201);
    }



    public function get_teachers()
    {
        $teachers = Teacher::all();
        return response()->json([
            'message' => 'Teachers with class timings and courses found successfully.',
            'teachers' => $teachers,
        ], 200);
    }

    public function get_filtered_teachers($studentID, $courseID)
    {
        try {
            $studentID = $studentID === 'null' ? null : $studentID;
            $courseID = $courseID === 'null' ? null : $courseID;

            if (is_null($courseID) && is_null($studentID)) {
                return response()->json([
                    'message' => 'Please select at least one filter.',
                ], 400);
            }

            if ($courseID && !$studentID) {
                $teachers = CourseTeacher::where('courseID', $courseID)
                    ->with('teacher')
                    ->get()
                    ->pluck('teacher');
            } elseif ($studentID && !$courseID) {
                $teachers = TeacherAllotment::where('studentID', $studentID)
                    ->with('teacher')
                    ->get()
                    ->pluck('teacher');
            } else {
                $teachers = TeacherAllotment::where('studentID', $studentID)
                    ->where('courseID', $courseID)
                    ->with('teacher')
                    ->get()
                    ->pluck('teacher');
            }

            return response()->json([
                'message' => $teachers->isEmpty()
                    ? 'No teachers found for the provided criteria.'
                    : 'Teachers found successfully.',
                'teachers' => $teachers,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching teachers.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }



    public function assign_login_credentials(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|integer',
                'teacherID' => 'required|integer|exists:teachers,id',
                'name' => 'required|string|max:255',
                'email' => 'required|string|email',
                'password' => 'required|string|min:6',
                'isEdit' => 'required|boolean',
            ]);

            $teacher = Teacher::findOrFail($validatedData['teacherID']);

            $teacher->update([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'teach_id' => $validatedData['id'],
                'status' => 'allowed',
            ]);

            if ($validatedData['isEdit']) {
                $user = User::where('teacher_id', $teacher->id)->first();
                if ($user) {
                    $user->update([
                        'name' => $validatedData['name'],
                        'email' => $validatedData['email'],
                        'password' => Hash::make($validatedData['password']),
                    ]);
                } else {
                    return response()->json([
                        'message' => "User not found"
                    ], 404);
                }
            } else {
                $user = User::create([
                    'name' => $validatedData['name'],
                    'email' => $validatedData['email'],
                    'password' => Hash::make($validatedData['password']),
                    'role' => 'teacher',
                    'teacher_id' => $teacher->id,
                ]);
            }

            $title = $validatedData['isEdit'] ? 'Login Credentials Updated' : 'Login Credentials Assigned';
            $subtitle = '';
            $body = 'Dear ' . $teacher->name . ',<br><br>Your login credentials have been ' . ($validatedData['isEdit'] ? 'updated' : 'assigned') . '. Below are your updated credentials:<br><br>' .
                '<strong>Teacher ID:</strong> ' . $teacher->teach_id . '<br>' .
                '<strong>Name:</strong> ' . $teacher->name . '<br>' .
                '<strong>Email:</strong> ' . $teacher->email . '<br>' .
                '<strong>Password:</strong> ' . $validatedData['password'] . '<br><br>' .
                'You can now access the portal using the above credentials.<br><br>Thanks,<br>The HolyVibes Team';

            Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));

            return response()->json([
                'message' => $validatedData['isEdit']
                    ? 'Login credentials updated successfully'
                    : 'Login credentials assigned successfully',
                'teacher_id' => $teacher->id,
                'user_id' => $user->id,
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing your request',
                'error' => $e->getMessage(),
                'trace' => config('app.debug') ? $e->getTrace() : null,
            ], 500);
        }
    }

    public function delete_teacher(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required|exists:teachers,id',
        ]);
        $teacherID = $validatedData['teacherID'];
        $teacher = Teacher::find($teacherID);
        if (!$teacher) {
            return response()->json([
                'message' => 'Teacher not found.',
            ], 404);
        }
        $email = $teacher->email;
        $name = $teacher->name;
        $teacher->delete();
        $title = 'Account Deletion Notice';
        $subtitle = 'Your Teacher Account Has Been Removed';
        $body = 'Dear ' . $name . ',<br><br>
        Your teacher account has been deleted from our system.<br><br>
        If you believe this was a mistake or have any questions, please contact our support team.<br><br>
        Regards,<br>The HolyVibes Team';
        Mail::to($email)->send(new ClassMail($title, $subtitle, $body));
        return response()->json([
            'message' => 'Teacher deleted and notification email sent.',
        ], 200);
    }


    public function get_teacher_course(Request $request)
    {
        $user = $request->get('user');
        $courses = CourseTeacher::with('course')->where('teacherID', $user->teacher_id)->get();
        return response()->json([
            'message' => 'Course found successfully.',
            'course' => $courses
        ], 200);
    }

    public function get_std_performance(Request $request)
    {
        $teacherID = $request->get('user')->teacher_id;

        $studentPerformance = StudentPerformance::with(['student', 'course', 'class'])
            ->where('teacherID', $teacherID)
            ->get();

        if ($studentPerformance->isEmpty()) {
            return response()->json([
                'message' => 'No student performance records found for this teacher.',
                'data' => [],
            ], 404);
        }

        return response()->json([
            'message' => 'Student performance data fetched successfully.',
            'data' => $studentPerformance,
        ], 200);
    }

    public function remove_allocated_course($courseID)
    {
        $course = Course::find($courseID);

        if (!$course) {
            return response()->json([
                'success' => false,
                'message' => 'Course not found',
            ], 404);
        }
        $courseName = $course->name ?? 'Unnamed Course';
        $courseTeachers = CourseTeacher::where('courseID', $courseID)->get();
        foreach ($courseTeachers as $entry) {
            $teacher = Teacher::find($entry->teacherID);
            if ($teacher) {
                $title = 'Course Allocation Removed';
                $subtitle = '';
                $body = 'Dear ' . $teacher->name . ',<br><br>' .
                    'The course "<strong>' . $courseName . '</strong>" (Course ID: ' . $courseID . ') assigned to you has been removed by the admin.<br>' .
                    'If you have any questions or concerns, please contact the administration.<br><br>' .
                    'Thank you,<br>The HolyVibes Team';
                Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
            }
        }
        ClassCourse::where('courseID', $courseID)->delete();
        StudentClassTimings::where('courseID', $courseID)->delete();
        TeacherAllotment::where('courseID', $courseID)->delete();
        CourseTeacher::where('courseID', $courseID)->delete();
        TeacherClassTimings::where('courseID', $courseID)->delete();
        StudentPerformance::where('courseID', $courseID)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Course and related records removed successfully',
        ], 200);
    }

    public function block_or_unblock_teacher(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required|exists:teachers,id',
        ]);

        $teacher = Teacher::find($validatedData['teacherID']);
        $currentStatus = "";
        if ($teacher->status === 'allowed') {
            $teacher->status = 'blocked';
            $currentStatus = 'blocked';
        } else {
            $teacher->status = 'allowed';
            $currentStatus = 'allowed';
        }

        $teacher->save();

        return response()->json([
            'message' => "Teacher {$currentStatus} successfully.",
            'status' => $teacher->status,
        ], 200);
    }
}
