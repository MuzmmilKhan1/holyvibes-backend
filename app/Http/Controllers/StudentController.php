<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\Student;
use App\Models\Billing;
use App\Models\ClassModel;
use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\StudentClassTimings;
use App\Models\StudentPerformance;
use App\Models\TeacherAllotment;
use App\Models\TeacherClassTimings;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\File;

class StudentController extends Controller
{
    public function register_student(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'guardian_name' => 'required|string|max:255',
                'email' => 'required|email|unique:students,email|unique:users,email',
                'password' => 'required|string|min:6',
                'contact_number' => 'required|string|max:20',
                'alternate_contact_number' => 'nullable|string|max:20',
                'date_of_birth' => 'required|date',
                'registration_date' => 'required|date',
                'preferred_language' => 'required|string|max:50',
                'signature' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            $filteredCourses = collect([]);

            if (!empty($request->courses)) {
                $filteredCourses = collect($request->courses)->map(function ($course) {
                    return [
                        'course_id' => $course['course_id'],
                        'course_name' => $course['course_name'],
                        'timings' => $course['timings'],
                    ];
                });
            }
            $student = Student::create([
                'name' => $request->name,
                'guardian_name' => $request->guardian_name,
                'email' => $request->email,
                'contact_number' => $request->contact_number,
                'alternate_contact_number' => $request->alternate_contact_number,
                'date_of_birth' => $request->date_of_birth,
                'registration_date' => $request->registration_date,
                'preferred_language' => $request->preferred_language,
                'signature' => $request->signature,
                'class_course_data' => empty($request->courses) ? null : $filteredCourses->toJson(),
                'status' => 'allowed',
            ]);
            $newUser = User::create([
                'student_id' => $student->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student',
            ]);

            if (!empty($request->courses)) {
                foreach ($request->courses as $course) {
                    $receiptImage = $course['billing']['receipt_image'];
                    $base64Receipt = base64_encode(file_get_contents($receiptImage->getRealPath()));
                    $mimeType = $receiptImage->getMimeType();
                    Billing::create([
                        'studentID' => $student->id,
                        'courseID' => $course['course_id'],
                        'receipt' => "data:$mimeType;base64,$base64Receipt",
                        'paymentMethod' => $course['billing']['payment_method'],
                        'paymentStatus' => 'pending',
                    ]);
                }
            }

            return response()->json([
                'message' => 'Student registered successfully!',
                'student' => $student,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
    }

    public function get_student()
    {
        try {
            $students = Student::all();
            return response()->json([
                'message' => 'Student found successfully!',
                'students' => $students,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function get_single_std_data($studentID)
    {
        try {
            $students = Student::all();
            return response()->json([
                'message' => 'Student found successfully!',
                'students' => $students,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function get_billing_details($studentID)
    {
        try {
            $billing_details = Billing::with(['course'])
                ->where('studentID', $studentID)
                ->get();
    
            if ($billing_details->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Billing details not found!',
                    'data' => null
                ], 404);
            }
    
            return response()->json([
                'success' => true,
                'message' => 'Billing details found successfully!',
                'data' => $billing_details
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function assign_login_credentials(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'id' => 'required|exists:students,id',
            'studentID' => 'required|string',
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
    
        $student = Student::find($request->id);
    
        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }
    
        $student->std_id = $request->studentID;
        $student->save(); // Make sure to save changes
    
        return response()->json([
            'message' => 'StudentID assigned successfully!',
        ], 200);
    }
    
    public function get_std_courses(Request $request)
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
        if (!$user || !$user->student_id) {
            return response()->json(['error' => 'Unauthorized or invalid student'], 403);
        }
        $studentID = $user->student_id;
        $allotments = TeacherAllotment::with(['course'])
            ->where('studentID', $studentID)
            ->get();
        $uniqueCourses = $allotments
            ->pluck('course')
            ->unique('id')
            ->values();
        return response()->json([
            'message' => 'Courses found successfully!',
            'courses' => $uniqueCourses
        ], 200);
    }

    public function get_std_courses_classes(Request $request, $courseID)
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
        if (!$user || !$user->student_id) {
            return response()->json(['error' => 'Unauthorized or invalid student'], 403);
        }

        $studentID = $user->student_id;
        $classes = StudentClassTimings::with(['class', 'course', 'class.teacherClassTimings'])
            ->whereNotNull('classID')
            ->where('classID', '!=', '')
            ->where('classID', '>', 0)
            ->where('courseID', $courseID)
            ->where('studentID', $studentID)
            ->get();

        if ($classes->isEmpty()) {

        return response()->json([
            'message' => 'No classes are available!',
        ], 404);
        }

        return response()->json([
            'message' => 'Class timings found successfully!',
            'classes' => $classes
        ], 200);
    }



    public function purchase_course(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'courseID' => 'required|integer|exists:courses,id',
            'paymentMethod' => 'required|string|in:bank transfer,jazzcash',
            'receipt' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'classTimings' => 'required|array|min:1',
            'classTimings.*.from' => 'required|date_format:H:i',
            'classTimings.*.to' => 'required|date_format:H:i|after:classTimings.*.from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }
        $course = Course::find($request->courseID);
        $studentID = $request->get('user')->student_id;
        $paymentMethod = $request->paymentMethod;
        $classTimings = $request->classTimings;
        $file = $request->file('receipt');
        $fileContents = File::get($file->getRealPath());
        $base64Receipt = base64_encode($fileContents);
        $mime = $file->getClientMimeType();
        $base64Receipt = "data:{$mime};base64," . $base64Receipt;
        $billing = new Billing();
        $billing->studentID = $studentID;
        $billing->courseID = $course->id;
        $billing->paymentMethod = $paymentMethod;
        $billing->receipt = $base64Receipt;
        $billing->paymentStatus = 'paid';
        $billing->save();
        $student = Student::find($studentID);
        $classCourseData = $student->class_course_data ? json_decode($student->class_course_data, true) : [];
        $newCourseData = [
            'timings' => $classTimings,
            'course_id' => (string) $course->id,
            'course_name' => $course->name,
        ];
        $classCourseData[] = $newCourseData;
        $student->class_course_data = json_encode($classCourseData);
        $student->save();
        return response()->json([
            'message' => 'Billing details have been sent to the admin. Admin will allot the classes soon.',
            'billing_id' => $billing->id
        ]);
    }


    public function get_performance(Request $request, $classID)
    {
        $studentID = $request->get('user')->student_id;

        $performance = StudentPerformance::with(['course', 'teacher', 'student'])
            ->where('classID', $classID)
            ->where('studentID', $studentID)
            ->first();

        if (!$performance) {
            return response()->json([
                'message' => 'No performance record found for this class and student.',
                'data' => null,
            ], 404);
        }

        return response()->json([
            'message' => 'Student performance fetched successfully.',
            'data' => $performance,
        ], 200);
    }


    public function delete_class_time($classTimingID)
    {
        $classTiming = StudentClassTimings::find($classTimingID);
        if (!$classTiming) {
            return response()->json([
                'message' => 'Class time not found.',
            ], 404);
        }
        $classTiming->delete();
        return response()->json([
            'message' => 'Student Classtime deleted succesfully.',
        ], 200);
    }

    public function delete_student($studentID)
    {
        $student = Student::find($studentID);
        if (!$student) {
            return response()->json([
                'message' => 'Student not found.',
            ], 404);
        }
        $student->delete();
        return response()->json([
            'message' => 'Student deleted successfully.',
        ], 200);
    }


}