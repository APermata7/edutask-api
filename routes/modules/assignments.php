<?php

use App\Modules\Assignments\Http\Controllers\AssignmentController;
use Illuminate\Support\Facades\Route;

Route::middleware(['assignment.json', 'auth:api'])->group(function () {
    Route::get('/assignments', [AssignmentController::class, 'index']);
    Route::get('/assignments/{assignment}', [AssignmentController::class, 'show']);
    Route::get('/classes/{classId}/assignments', [AssignmentController::class, 'byClass']);

    Route::middleware(['role:lecturer'])->group(function () {
        Route::post('/assignments', [AssignmentController::class, 'store']);
        Route::put('/assignments/{assignment}', [AssignmentController::class, 'update']);
        Route::patch('/assignments/{assignment}/publish', [AssignmentController::class, 'publish']);
        Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy']);
    });
});