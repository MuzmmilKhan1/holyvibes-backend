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
                'email' => 'required|email',
                'contact_number' => 'required|string|max:20',
                'alternate_contact_number' => 'nullable|string|max:20',
                'date_of_birth' => 'required|date',
                'registration_date' => 'required|date',
                'preferred_language' => 'required|string|max:50',
                'signature' => 'required|string',
                'courses' => 'required|array|min:1',
                'courses.*.course_id' => 'required|integer|exists:courses,id',
                'courses.*.course_name' => 'required|string',
                'courses.*.timings' => 'required|array|min:1',
                'courses.*.timings.*.from' => 'required|string',
                'courses.*.timings.*.to' => 'required|string',
                'courses.*.billing.receipt_image' => 'required|file|image|max:5048',
                'courses.*.billing.payment_method' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            $filteredCourses = collect($request->courses)->map(function ($course) {
                return [
                    'course_id' => $course['course_id'],
                    'course_name' => $course['course_name'],
                    'timings' => $course['timings'],
                ];
            });
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
                'class_course_data' => $filteredCourses->toJson(),
            ]);
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
            return response()->json([
                'message' => 'Your application has been submitted successfully and is pending admin approval',
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
            $billing_details = Billing::with(['course'])->where('studentID', $studentID)->get();
            return response()->json([
                'message' => 'Billing Details found successfully!',
                'billingDetails' => $billing_details,
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    public function assign_login_credentials(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'id' => 'required|exists:students,id',
                'studentID' => 'required|string',
                'name' => 'required|string|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:6',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }
            DB::beginTransaction();
            $student = Student::find($request->id);
            if (!$student) {
                return response()->json(['error' => 'Student not found'], 404);
            }
            $student->std_id = $request->studentID;
            $student->name = $request->name;
            $student->email = $request->email;
            $student->status = 'allowed';
            $student->save();
            if (User::where('student_id', $student->id)->exists()) {
                return response()->json(['error' => 'User account already exists for this student'], 409);
            }
            if (User::where('email', $student->email)->exists()) {
                return response()->json(['error' => 'This email is already taken by another user'], 409);
            }
            User::create([
                'student_id' => $student->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student'
            ]);
            Billing::where('studentID', $student->id)->update(['paymentStatus' => 'paid']);
            DB::commit();
            return response()->json([
                'message' => 'Login credentials assigned successfully!',
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong: ' . $e->getMessage()
            ], 500);
        }
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
        $classes = StudentClassTimings::with(['class', 'course'])->whereNotNull('classID')
            ->where('classID', '!=', '')
            ->where('classID', '>', 0)
            ->where('courseID', $courseID)
            ->where('studentID', $studentID)
            ->get();

        return response()->json([
            'message' => 'Class timings found successfully!',
            'classes' => $classes
        ], 200);
    }

    public function get_std_class_course_data(Request $request)
    {
        try {
            $data = $request->all();
            $result = [];
            foreach ($data as $item) {
                $classID = $item['classID'];
                $courseID = $item['courseID'];
                $classTimeID = $item['classTimeID'];
                $class = ClassModel::find($classID);
                $course = Course::find($courseID);
                $classTime = TeacherClassTimings::find($classTimeID);

                $result[] = [
                    'class' => $class,
                    'course' => $course,
                    'classTime' => $classTime,
                ];
            }
            return response()->json($result, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }

    // public function get_allocated_class_course($studentID)
    // {
    //     try {
    //         $data = Enrollment::with(['class.classTimings', 'course'])->where('studentId', $studentID)->get();
    //         return response()->json($data, 200);

    //     } catch (\Exception $e) {
    //         return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
    //     }
    // }


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
        $studentID = $request->get('user')->std_id;

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



}