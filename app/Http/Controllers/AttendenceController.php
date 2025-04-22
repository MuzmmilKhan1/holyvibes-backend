<?php

namespace App\Http\Controllers;

use App\Models\Attendence;
use Illuminate\Http\Request;

class AttendenceController extends Controller
{
    public function add_edit_attendence(Request $request, $attendenceID)
    {
        $validated = $request->validate([
            'classId' => 'required|exists:classes,id',
            'studentId' => 'required|exists:students,id',
            'date' => 'required|date',
            'status' => 'required|in:present,absent',
        ]);

        if ($attendenceID == 0) {
            $isStdAttendenceExists = Attendence::where('classID', $validated['classId'])
                ->where('studentID', $validated['studentId'])
                ->where('date', $validated['date'])
                ->exists();

            if ($isStdAttendenceExists) {
                return response()->json(['message' => 'Attendence already exists for this student on this date'], 422);
            }
            $attendence = new Attendence();
            $attendence->classID = $validated['classId'];
            $attendence->studentID = $validated['studentId'];
            $attendence->date = $validated['date'];
            $attendence->status = $validated['status'];
            $attendence->save();

            return response()->json(['message' => 'Attendence recorded successfully'], 201);
        } else {
            $attendence = Attendence::find($attendenceID);
            if (!$attendence) {
                return response()->json(['message' => 'Attendance not found'], 404);
            }
            $existing = Attendence::where('studentID', $validated['studentId'])
                ->where('date', $validated['date'])
                ->where('id', '!=', $attendenceID)
                ->first();
            if ($existing) {
                return response()->json([
                    'message' => 'An attendance record already exists for this student on the same date.',
                ], 409);
            }
            $attendence->classID = $validated['classId'];
            $attendence->studentID = $validated['studentId'];
            $attendence->date = $validated['date'];
            $attendence->status = $validated['status'];
            $attendence->save();
            return response()->json(['message' => 'Attendance updated successfully'], 200);

        }
    }

    public function get_attendence($classId)
    {
        $attendences = Attendence::where('classID', $classId)->with(['student'])->get();
        if ($attendences->isEmpty()) {
            return response()->json(['message' => 'No attendence records found for this class'], 404);
        }
        return response()->json([
            'message' => 'Attendence records retrieved successfully',
            'attendences' => $attendences,
        ], 200);

    }

}
