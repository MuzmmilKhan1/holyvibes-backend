<?php

use App\Http\Controllers\AttendenceController;
use App\Http\Controllers\TeacherAllotmentController;
use App\Http\Controllers\TeacherClassTimingsController;
use App\Http\Controllers\TeacherController;
use App\Http\Middleware\TeacherAuthMiddleware;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\CourseContoller;
use App\Http\Controllers\EventController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentPolicyController;

Route::get('/ping', function () {
    return 'pong';
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, "login"]);
    Route::post('/create-account', [AuthController::class, "signup"]);
    Route::get('/create-admin', [AuthController::class, "create_admin"]);
    Route::get('/get', [AuthController::class, "get_user"]);

});

// course routes
Route::prefix('course')->group(function () {
    Route::post('/create-course', [CourseContoller::class, "create_course"]);
    Route::get('/get', [CourseContoller::class, "get"]);
    // Route::get('/get-teacher-courses-time', [CourseContoller::class, "get_teacher_courses_time"]);
});

// teacher routes
Route::prefix('teacher')->group(function () {
    Route::post('/requested-teacher', [TeacherController::class, "handle_requested_teacher"]);
    Route::post('/assign_login_credentials', [TeacherController::class, "assign_login_credentials"]);
    Route::get('/get', [TeacherController::class, "get_teachers"]);
    Route::post('/block', [TeacherController::class, "block_or_unblock_teacher"]);
    Route::post('/delete', [TeacherController::class, "delete_teacher"]);
    Route::get('/get-teacher-course', [TeacherController::class, "get_teacher_course"])->middleware([TeacherAuthMiddleware::class]);
});


// class time route
Route::prefix('class-time')->group(function () {
    // Route::post('/add-edit-time', [ClassTimingsController::class, "add_edit_time"]);
    Route::delete('/delete/{classTimingID}', [TeacherClassTimingsController::class, "delete_class_time"]);
    Route::get('/get_class_time/{teacherID}', [TeacherClassTimingsController::class, "get_teacher_class_time"]);
});


// class routes
Route::prefix('class')->group(function () {
    Route::post('/create', [ClassController::class, "create_class"])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/get', [ClassController::class, "get_teacher_classes"])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/{classId}/students', [ClassController::class, "get_class_students"]);

    // Route::get('/get-all', [ClassController::class, "get_all"]);
    // Route::get('/get/single-class-data/{classID}', [ClassController::class, "get_single_class_data"]);
    // Route::get('/get/{courseID}', [ClassController::class, "get_class"]);
    // Route::get('/get/class-time/{classID}', [ClassController::class, "get_class_time"]);
    // Route::put('/edit', [ClassController::class, "edit_class"]);
    // Route::put('/edit/by-teacher', [ClassController::class, "edit_by_teacher_class"]);
});

// student policy routes
Route::prefix('student-policy')->group(function () {
    Route::post('/create-and-edit', [StudentPolicyController::class, "create_edit_policy"]);
    Route::get('/get', [StudentPolicyController::class, "get_policy"]);
    Route::delete('/delete/{policyID}', [StudentPolicyController::class, "delete_policy"]);

});

// student event routes
Route::prefix('event')->group(function () {
    Route::post('/create-event', [EventController::class, 'create_or_updateEvent']);
    Route::get('/get', [EventController::class, 'get_events']);
    Route::get('/get-event-members/{eventId}', [EventController::class, 'get_event_members']);
});

// student routes
Route::prefix('student')->group(function () {
    Route::post('/register', [StudentController::class, 'register_student']);
    Route::get('/get', [StudentController::class, 'get_student']);
    Route::get('/get/{studentID}', [StudentController::class, 'get_single_std_data']);
    Route::get('/get/billing-details/{studentID}', [StudentController::class, 'get_billing_details']);
    Route::post('/assign_login_credentials', [StudentController::class, "assign_login_credentials"]);
    Route::get('/get-std-courses', [StudentController::class, 'get_std_courses']);
    Route::get('/get-course-classes/{courseID}', [StudentController::class, 'get_std_courses_classes']);


    // Route::post('/get/requested-class-course', [StudentController::class, "get_std_class_course_data"]);
    // Route::get('/get/allocated-class-course/{studentID}', [StudentController::class, "get_allocated_class_course"]);
});


// teacher allotment routes
Route::prefix('teacher-allotment')->group(function () {
    Route::post('/allot', [TeacherAllotmentController::class, 'allot_teacher']);
    Route::get('/get', [TeacherAllotmentController::class, 'get_allotment']);
    Route::get('/get-teacher-allotment', [TeacherAllotmentController::class, 'get_teacher_allotment'])->middleware([TeacherAuthMiddleware::class]);
});



// attendence routes
Route::prefix('attendence')->group(function () {
    Route::post('/add-edit/{attendenceID}', [AttendenceController::class, 'add_edit_attendence'])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/get/{classId}', [AttendenceController::class, 'get_attendence'])->middleware([TeacherAuthMiddleware::class]);
});