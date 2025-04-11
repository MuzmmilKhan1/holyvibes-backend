<?php

namespace App\Http\Controllers;
use App\Models\Student;
use Illuminate\Http\Request;
class StudentController extends Controller
{
    public function create_student(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'date_of_birth' => 'required|date',
            'guardian_name' => 'required|string',
            'email' => 'required|email',
            'contact_number' => 'required|string',
            'alternate_contact_number' => 'nullable|string',
            'preferred_language' => 'nullable|string',
            'signature' => 'nullable|string',
            'registration_date' => 'required|date',
        ]);
        $student = Student::create($validated);
        return response()->json(['message' => 'Student created successfully', 'student' => $student]);
    }
}
