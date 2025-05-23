<?php

namespace App\Http\Controllers;

use App\Mail\ClassMail;
use App\Models\Course;
use App\Models\CourseTeacher;
use App\Models\Outline;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;


class CourseContoller extends Controller
{
    public function create_course(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'id' => 'required|integer',
                'name' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'courseDuration' => 'required|string|max:100',
                'image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            ]);

            if ($validatedData['id'] == 0) {
                if (!$request->hasFile('image')) {
                    return response()->json([
                        'message' => 'Image is required for creating a new course',
                    ], 422);
                }

                $imageFile = $request->file('image');
                $imageContent = file_get_contents($imageFile->getRealPath());
                $imageBase64 = base64_encode($imageContent);
                $mimeType = $imageFile->getMimeType();

                $course = Course::create([
                    'name' => $validatedData['name'],
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'course_duration' => $validatedData['courseDuration'],
                    'image' => "data:$mimeType;base64,$imageBase64",
                ]);

                $students = Student::all();
                $teachers = Teacher::all();

                $title = 'New Course Available!';
                $subtitle = 'Check out our latest offering';
                $body = 'We are excited to announce a new course: <strong>' . $course->name . '</strong>.<br><br>'
                    . 'Description: ' . $course->description . '<br>'
                    . 'Price: ' . $course->price . '<br>'
                    . 'Duration: ' . $course->course_duration . '<br><br>'
                    . 'Enroll now and enhance your skills!<br><br>'
                    . 'Regards,<br>The HolyVibes Team';

                foreach ($students as $student) {
                    Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));
                }

                foreach ($teachers as $teacher) {
                    Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
                }

                return response()->json([
                    'message' => 'Course created successfully!',
                    'course_id' => $course->id,
                ], 201);
            } else {
                $course = Course::find($validatedData['id']);
                if (!$course) {
                    return response()->json([
                        'message' => 'Course not found',
                    ], 404);
                }

                $updateData = [
                    'name' => $validatedData['name'],
                    'description' => $validatedData['description'],
                    'price' => $validatedData['price'],
                    'course_duration' => $validatedData['courseDuration'],
                ];

                if ($request->hasFile('image')) {
                    $imageFile = $request->file('image');
                    $imageContent = file_get_contents($imageFile->getRealPath());
                    $imageBase64 = base64_encode($imageContent);
                    $mimeType = $imageFile->getMimeType();
                    $updateData['image'] = "data:$mimeType;base64,$imageBase64";
                }

                $course->update($updateData);

                return response()->json([
                    'message' => 'Course updated successfully!',
                    'course_id' => $course->id,
                ], 200);
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while processing the course',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function get()
    {
        $courses = Course::all();
        return response()->json([
            'message' => 'Courses found successfully!',
            'course' => $courses,
        ], 201);
    }

    public function outlines($courseID)
    {
        if (!$courseID) {
            return response()->json([
                'message' => 'Course ID is required!',
            ], 400);
        }
        $courseOutlines = Outline::where('courseID', $courseID)->get();
        if ($courseOutlines->isEmpty()) {
            return response()->json([
                'message' => 'No outlines found for this course.',
            ], 404);
        }

        return response()->json([
            'message' => 'Course outlines retrieved successfully!',
            'outlines' => $courseOutlines,
        ], 200);
    }


    public function add_outlines(Request $request, $courseID, $outlineID)
    {
        $validatedData = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
        ]);
        if ((int) $outlineID === 0) {
            $outline = Outline::create([
                'courseID' => $courseID,
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
            ]);
            return response()->json([
                'message' => 'Course outline added successfully!',
                'course' => $outline,
            ], 201);
        } else {
            $outline = Outline::find($outlineID);
            if (!$outline) {
                return response()->json(['message' => 'Outline not found'], 404);
            }
            $outline->update([
                'title' => $validatedData['title'],
                'description' => $validatedData['description'],
            ]);
            return response()->json([
                'message' => 'Course outline updated successfully!',
                'course' => $outline,
            ], 200);
        }
    }

    public function delete_outlines($outlineId)
    {
        $outline = Outline::find($outlineId);

        if (!$outline) {
            return response()->json([
                'message' => 'Outline not found',
            ], 404);
        }

        $outline->delete();

        return response()->json([
            'message' => 'Course outline deleted successfully!',
        ], 200);
    }

    public function assign_course(Request $request)
    {
        $validatedData = $request->validate([
            'teacherID' => 'required|integer|exists:teachers,id',
            'courseID' => 'required|integer|exists:courses,id',
        ]);

        $existing = CourseTeacher::where('teacherID', $validatedData['teacherID'])
            ->where('courseID', $validatedData['courseID'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'This course is already assigned to the teacher.',
            ], 409);
        }

        CourseTeacher::create([
            'teacherID' => $validatedData['teacherID'],
            'courseID' => $validatedData['courseID'],
        ]);

        $teacher = Teacher::find($validatedData['teacherID']);
        $course = Course::find($validatedData['courseID']);

        if ($teacher && $course) {
            $title = 'New Course Assigned';
            $subtitle = '';
            $body = 'Dear ' . $teacher->name . ',<br><br>' .
                'You have been assigned to the course "<strong>' . ($course->name ?? 'Unnamed Course') . '</strong>" (Course ID: ' . $course->id . ') by the admin.<br>' .
                'Please check your dashboard for more details.<br><br>' .
                'Thank you,<br>The HolyVibes Team';

            Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
        }

        return response()->json([
            'message' => 'Course assigned to teacher successfully.',
        ], 200);
    }


    public function get_teacher_course($teacherID)
    {
        $courses = CourseTeacher::with('course', )
            ->where('teacherID', $teacherID)
            ->get();
        return response()->json([
            'message' => 'Courses fetched successfully',
            'courses' => $courses,
        ], 200);
    }


    public function delete_course($courseID)
    {
        $course = Course::find($courseID);
        if (!$course) {
            return response()->json([
                'message' => 'Course not found.',
            ], 404);
        }
        $students = Student::all();
        $teachers = Teacher::all();
        $title = 'Course Deletion Notice';
        $subtitle = 'Important: A course you were enrolled in has been deleted';
        $body = 'We regret to inform you that the course: <strong>' . $course->name . '</strong> has been deleted.<br><br>'
            . 'If you have any questions or concerns, please contact support.<br><br>'
            . 'Regards,<br>The HolyVibes Team';
        foreach ($students as $student) {
            Mail::to($student->email)->send(new ClassMail($title, $subtitle, $body));
        }
        foreach ($teachers as $teacher) {
            Mail::to($teacher->email)->send(new ClassMail($title, $subtitle, $body));
        }
        $course->delete();
        return response()->json([
            'message' => 'Course deleted successfully, and notifications have been sent.',
        ], 200);
    }

}
