<?php

use App\Http\Controllers\TeacherController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CourseContoller;


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
});

// teacher routes
Route::prefix('teacher')->group(function () {
    Route::post('/requested-teacher', [TeacherController::class, "handle_requested_teacher"]);
    Route::post('/assign_login_credentials', [TeacherController::class, "assign_login_credentials"]);
    Route::get('/get', [TeacherController::class, "get_teachers"]);
    Route::post('/block', [TeacherController::class, "assign_login_credentials"]);
    Route::post('/delete', [TeacherController::class, "delete_teacher"]);

});

