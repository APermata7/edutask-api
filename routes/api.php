<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\EnrollmentController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Class management routes
    Route::get('/classes', [ClassRoomController::class, 'index']);
    Route::get('/classes/{id}', [ClassRoomController::class, 'show']);
    
    // Lecturer only
    Route::middleware('role:lecturer')->group(function () {
        Route::post('/classes', [ClassRoomController::class, 'store']);
        Route::put('/classes/{id}', [ClassRoomController::class, 'update']);
        Route::delete('/classes/{id}', [ClassRoomController::class, 'destroy']);
        Route::get('/classes/{id}/invite-code', [ClassRoomController::class, 'getInviteCode']);
    });

    // Student only
    Route::middleware('role:student')->group(function () {
        Route::post('/classes/join-by-code', [ClassRoomController::class, 'joinByCode']);
    });

    // Enrollment management routes (Lecturer only)
    Route::middleware('role:lecturer')->group(function () {
        Route::get('/classes/{classId}/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/classes/{classId}/enrollments', [EnrollmentController::class, 'store']);
        Route::delete('/classes/{classId}/enrollments/{enrollmentId}', [EnrollmentController::class, 'destroy']);
    });

    // Check enrollment (both lecturer and student)
    Route::get('/classes/{classId}/check-enrollment/{studentId}', [EnrollmentController::class, 'checkEnrollment']);
});

