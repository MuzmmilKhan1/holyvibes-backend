<?php

namespace App\Http\Controllers;

use App\Models\ClassEnrollment;
use App\Models\Student;
use App\Models\Billing;
use App\Models\ClassModel;
use App\Models\ClassTimings;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class StudentController extends Controller
{
    public function register_student(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'guardian_name' => 'required|string|max:255',
                'email' => 'required|',
                'contact_number' => 'required|string|max:20',
                'alternate_contact_number' => 'required|string|max:20',
                'date_of_birth' => 'required|date',
                'registration_date' => 'required|date',
                'preferred_language' => 'required|string|max:50',
                'signature' => 'required|string',
                'courses' => 'required|array|min:1',
                'courses.*.courseID' => 'required|integer|',
                'courses.*.classID' => 'required|integer|',
                'courses.*.classTimeID' => 'required|integer|',
                'courses.*.billing.payment_method' => 'required|string',
                'courses.*.billing.receipt_image' => 'required|file|image|mimes:jpeg,png,jpg|max:5048',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            $filteredCourses = collect($request->courses)->map(function ($course) {
                return [
                    'courseID' => $course['courseID'],
                    'classID' => $course['classID'],
                    'classTimeID' => $course['classTimeID'],
                ];
            });
            $coursesJson = $filteredCourses->toJson();
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
                'class_course_data' => $coursesJson,

            ]);

            foreach ($request->courses as $course) {
                $receiptImage = $course['billing']['receipt_image'];
                $base64Receipt = base64_encode(file_get_contents($receiptImage->getRealPath()));
                $mimeType = $receiptImage->getMimeType();
                Billing::create([
                    'studentID' => $student->id,
                    'courseID' => $course['courseID'],
                    'receipt' => "data:$mimeType;base64,$base64Receipt",
                    'paymentMethod' => $course['billing']['payment_method'],
                    'paymentStatus' => 'pending',
                ]);
            }

            return response()->json([
                'message' => 'Student registered successfully!',
                'student' => $student,
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
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
                'id' => 'required',
                'name' => 'required|string|max:255',
                'email' => 'required|',
                'password' => 'required|string|min:6',
            ]);
            if ($validator->fails()) {
                return response()->json(['error' => $validator->errors()], 422);
            }

            // update student data 
            $student = Student::find($request->id);
            $student->name = $request->name;
            $student->email = $request->email;
            $student->status = 'allowed';


            // create new user
            User::create([
                'student_id' => $student->id,
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'student'
            ]);

            // convert json string json 
            $class_course_data = json_decode($student->class_course_data, true);

            //enrollment in class and fill class seat 
            foreach ($class_course_data as $items) {
                Enrollment::create([
                    'studentId' => $student->id,
                    'classId' => $items['classID'],
                    'courseId' => $items['courseID'],
                    'classTimeId' => $items['classTimeID'],
                ]);
                $class = ClassModel::find($items['classID']);
                if ($class) {
                    $class->filled_seats = $class->filled_seats + 1;
                    $class->save();
                }
            }

            // change billing status
            $billing = Billing::where('studentID', $student->id)->get();
            foreach ($billing as $items) {
                $items->paymentStatus = 'paid';
                $items->save();
            }

            return response()->json([
                'message' => 'Login credentials assign succesfully!',
            ], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
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
                $classTime = ClassTimings::find($classTimeID);

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

    public function get_allocated_class_course($studentID)
    {
        try {
            $data = Enrollment::with(['class.classTimings', 'course'])->where('studentId', $studentID)->get();
            return response()->json($data, 200);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong: ' . $e->getMessage()], 500);
        }
    }


}