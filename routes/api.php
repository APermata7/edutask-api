<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\SubmissionController;
use App\Http\Controllers\ClassRoomController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\GradeController;
use App\Http\Controllers\FeedbackController;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:api'])->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/profile/avatar', [AuthController::class, 'uploadAvatar']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::get('/classes', [ClassRoomController::class, 'index']);
    Route::get('/classes/{id}', [ClassRoomController::class, 'show']);

    Route::middleware('role:lecturer')->group(function () {
        Route::post('/classes', [ClassRoomController::class, 'store']);
        Route::put('/classes/{id}', [ClassRoomController::class, 'update']);
        Route::delete('/classes/{id}', [ClassRoomController::class, 'destroy']);
        Route::get('/classes/{id}/invite-code', [ClassRoomController::class, 'getInviteCode']);
    });

    Route::middleware('role:student')->group(function () {
        Route::post('/classes/join-by-code', [ClassRoomController::class, 'joinByCode']);
    });

    Route::middleware('role:lecturer')->group(function () {
        Route::get('/classes/{classId}/enrollments', [EnrollmentController::class, 'index']);
        Route::post('/classes/{classId}/enrollments', [EnrollmentController::class, 'store']);
        Route::delete('/classes/{classId}/enrollments/{enrollmentId}', [EnrollmentController::class, 'destroy']);
    });

    Route::get('/classes/{classId}/check-enrollment/{studentId}', [EnrollmentController::class, 'checkEnrollment']);
});

// Route untuk Submission (Tugas)
Route::middleware(['auth:api'])->group(function () {
    Route::apiResource('/submissions', SubmissionController::class);

    Route::get('/grades', [GradeController::class, 'index']);
    Route::get('/grades/{id}', [GradeController::class, 'show']);

    Route::middleware('role:lecturer')->group(function () {
        Route::post('/classes/{classId}/grade', [GradeController::class, 'store']);
        Route::post('/grades', [GradeController::class, 'store']);
        Route::put('/grades/{id}', [GradeController::class, 'update']);
        Route::delete('/grades/{id}', [GradeController::class, 'destroy']);
    });

    Route::get('/feedback', [FeedbackController::class, 'index']);
    Route::get('/feedback/{id}', [FeedbackController::class, 'show']);
    Route::post('/feedback', [FeedbackController::class, 'store']);
    Route::put('/feedback/{id}', [FeedbackController::class, 'update']);
    Route::delete('/feedback/{id}', [FeedbackController::class, 'destroy']);
});

require __DIR__ . '/modules/assignments.php';
