<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\CourseContoller;
use App\Http\Controllers\EventController;
use App\Http\Controllers\StudentPolicyController;

Route::get('/ping', function () {
    return 'pong';
});

// Auth routes
Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, "login"]);
    Route::post('/create-account', [AuthController::class, "signup"]);
    Route::get('/create-admin', [AuthController::class, "create_admin"]);
});


// course routes
Route::prefix('course')->group(function () {
    Route::post('/create-course', [CourseContoller::class, "create_course"]);
    Route::get('/get', [CourseContoller::class, "get"]);
    Route::get('/get-teacher-courses-time', [CourseContoller::class, "get_teacher_courses_time"]);
});

// teacher routes
Route::prefix('teacher')->group(function () {
    Route::post('/requested-teacher', [TeacherController::class, "handle_requested_teacher"]);
    Route::post('/assign_login_credentials', [TeacherController::class, "assign_login_credentials"]);
    Route::get('/get', [TeacherController::class, "get_teachers"]);
    Route::post('/block', [TeacherController::class, "block_or_unblock_teacher"]);
    Route::post('/delete', [TeacherController::class, "delete_teacher"]);
});


// class routes
Route::prefix('class')->group(function () {
    Route::post('/create', [ClassController::class, "create_class"]);
    Route::get('/get', [ClassController::class, "get_teacher_classes"]);
    Route::get('/get-all', [ClassController::class, "get_all"]);
    Route::get('/get/single-class-data/{classID}', [ClassController::class, "get_single_class_data"]);
    Route::get('/get/{courseID}', [ClassController::class, "get_class"]);
    Route::get('/get/class-time/{classID}', [ClassController::class, "get_class_time"]);
    Route::put('/edit', [ClassController::class, "edit_class"]);
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
});
