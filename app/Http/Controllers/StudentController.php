<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Billing;
use Illuminate\Http\Request;
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
                'class_course_data' => $coursesJson

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
}