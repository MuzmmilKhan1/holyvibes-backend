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
use App\Http\Controllers\StudentPerformanceController;
use App\Http\Controllers\StudentPolicyController;
use App\Http\Middleware\StudentAuthMiddleware;

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
    Route::get('/outlines/{courseID}', [CourseContoller::class, "outlines"]);
    Route::post('/add-outlines/{courseID}/{outlineID}', [CourseContoller::class, "add_outlines"]);
    Route::delete('/delete-outline/{outlineId}', [CourseContoller::class, "delete_outlines"]);
    Route::post('/assign-course', [CourseContoller::class, "assign_course"]);
    Route::get('/get-teacher-assiged-course/{teacherID}', [CourseContoller::class, "get_teacher_course"]);
    Route::delete('/delete/{courseID}', [CourseContoller::class, "delete_course"]);

});

// teacher routes
Route::prefix('teacher')->group(function () {
    Route::post('/requested-teacher', [TeacherController::class, "handle_requested_teacher"]);
    Route::post('/assign_login_credentials', [TeacherController::class, "assign_login_credentials"]);
    Route::get('/get', [TeacherController::class, "get_teachers"]);
    Route::post('/block', [TeacherController::class, "block_or_unblock_teacher"]);
    Route::post('/delete', [TeacherController::class, "delete_teacher"]);
    Route::get('/get-teacher-course', [TeacherController::class, "get_teacher_course"])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/get-std-performance',  [TeacherController::class, "get_std_performance"])->middleware([TeacherAuthMiddleware::class]);
    Route::delete('/remove-allocated-course/{courseID}', [TeacherController::class, "remove_allocated_course"]);
});


// class time route
Route::prefix('class-time')->group(function () {
    Route::delete('/delete/{classTimingID}', [TeacherClassTimingsController::class, "delete_class_time"]);
    Route::get('/get_class_time/{teacherID}', [TeacherClassTimingsController::class, "get_teacher_class_time"]);
});


// class routes
Route::prefix('class')->group(function () {
    Route::post('/create', [ClassController::class, "create_class"])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/get', [ClassController::class, "get_teacher_classes"])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/{classId}/students', [ClassController::class, "get_class_students"]);
    Route::put('/edit/{classID}', [ClassController::class, "edit_class"]);
    Route::post('/assign-students', [ClassController::class, "assign_class"]);
    Route::get('/get-students', [ClassController::class, "get_students"])->middleware([TeacherAuthMiddleware::class]);
    Route::delete('/remove-std/{classID}/{studentID}/{stdClassTimingID}', [ClassController::class, "remove_students"]);
    Route::delete('/delete/{classID}', [ClassController::class, "delete_class"]);
    Route::get('/get-all', [ClassController::class, "get_all"]);
});

// student policy routes
Route::prefix('student-policy')->group(function () {
    Route::post('/create-and-edit', [StudentPolicyController::class, "create_edit_policy"]);
    Route::get('/get', [StudentPolicyController::class, "get_policy"]);
    Route::delete('/delete/{policyID}', [StudentPolicyController::class, "delete_policy"]);
});

// event routes
Route::prefix('event')->group(function () {
    Route::post('/create-event', [EventController::class, 'create_or_updateEvent']);
    Route::get('/get', [EventController::class, 'get_events']);
    Route::get('/get-event-members/{eventId}', [EventController::class, 'get_event_members']);
    Route::get('/get-std-events', [EventController::class, 'get_std_events'])->middleware([StudentAuthMiddleware::class]);
    Route::post('/event-payment', [EventController::class, 'event_payment'])->middleware([StudentAuthMiddleware::class]);
    Route::get('/get-std-event-billing/{studentID}/{eventId}', [EventController::class, 'get_std_event_billing']);
    Route::put('/update-payemnt-status', [EventController::class, 'update_payemnt_status']);
    Route::get('/join-cancel-membership/{eventID}/{studentID}', [EventController::class, 'join_cancel_membership']);
    Route::post('/add-students/{eventID}', [EventController::class, 'add_students']);
    Route::delete('/delete/{eventID}', [EventController::class, 'delete_event']);


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
    Route::post('/purchase-course', [StudentController::class, 'purchase_course'])->middleware([StudentAuthMiddleware::class]);
    Route::get('/get-performance/{classID}', [StudentController::class, 'get_performance'])->middleware([StudentAuthMiddleware::class]);
    Route::delete('/delete-std-time/{classTimingID}', [StudentController::class, "delete_class_time"]);
    Route::delete('/delete/{studentID}', [StudentController::class, "delete_student"]);

});


// teacher allotment routes
Route::prefix('teacher-allotment')->group(function () {
    Route::post('/allot', [TeacherAllotmentController::class, 'allot_teacher']);
    Route::get('/get', [TeacherAllotmentController::class, 'get_allotment']);
    Route::get('/get-teacher-allotment', [TeacherAllotmentController::class, 'get_teacher_allotment'])->middleware([TeacherAuthMiddleware::class]);
    Route::put('/update/{allotmentID}', [TeacherAllotmentController::class, 'update_allotment']);
    Route::delete('/delete/{allotmentID}', [TeacherAllotmentController::class, 'delete_allotment']);

});


// attendence routes
Route::prefix('attendence')->group(function () {
    Route::post('/add-edit/{attendenceID}', [AttendenceController::class, 'add_edit_attendence'])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/get/{classId}', [AttendenceController::class, 'get_attendence'])->middleware([TeacherAuthMiddleware::class]);
    Route::delete('/delete/{attendenceID}', [AttendenceController::class, 'delete_attendence']);
});



// std performance routes
Route::prefix('student-performance')->group(function () {
    Route::post('/add-edit', [StudentPerformanceController::class, 'add_edit_performance'])->middleware([TeacherAuthMiddleware::class]);
    Route::get('/get', [StudentPerformanceController::class, 'get_performance']);
    Route::delete('/delete/{reportID}', [StudentPerformanceController::class, 'delete_performance']);
    Route::put('/edit', [StudentPerformanceController::class, 'edit_performance']);

});