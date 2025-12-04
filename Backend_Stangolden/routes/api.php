<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\TryoutController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\AdminUserController;

/*
|----------------------------------------------------------------------------
| Public authentication
|----------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

/*
|----------------------------------------------------------------------------
| Public read-only endpoints
|----------------------------------------------------------------------------
*/
Route::get('modules', [ModuleController::class, 'index']);
Route::get('modules/{module}', [ModuleController::class, 'show']);

Route::get('tryouts', [TryoutController::class, 'index']);
Route::get('tryouts/{tryout}', [TryoutController::class, 'show']);

/*
|----------------------------------------------------------------------------
| Authenticated user endpoints (Sanctum)
|----------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    // auth helpers
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('user', [AuthController::class, 'user']);

    // Feedback (use plural /feedbacks to match frontend)
    Route::get('feedbacks', [FeedbackController::class, 'index']);
    Route::post('feedbacks', [FeedbackController::class, 'store']);
    Route::get('feedbacks/{feedback}', [FeedbackController::class, 'show']);
    Route::put('feedbacks/{feedback}', [FeedbackController::class, 'update']);
    Route::post('feedbacks/{feedback}', [FeedbackController::class, 'update']); // support _method=PUT form-data
    Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy']);
    Route::patch('feedbacks/{feedback}/toggle', [FeedbackController::class, 'toggleResolved']);

    // Admin area
    Route::prefix('admin')->group(function () {
        // Users management
        Route::get('users', [AdminUserController::class, 'index']);
        Route::post('users/fake', [AdminUserController::class, 'createFake']);
        Route::put('users/{user}', [AdminUserController::class, 'update']);
        Route::post('users/{user}/approve', [AdminUserController::class, 'approve']);
        Route::post('users/{user}/revoke', [AdminUserController::class, 'revoke']);
        Route::post('users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);
        Route::post('users/{user}/expiry', [AdminUserController::class, 'setExpiry']);
        Route::delete('users/{user}', [AdminUserController::class, 'destroy']);

        // Modules management (admin write)
        Route::get('modules', [ModuleController::class, 'index']);
        Route::get('modules/{module}', [ModuleController::class, 'show']);
        Route::post('modules', [ModuleController::class, 'store']);
        Route::post('modules/{module}', [ModuleController::class, 'update']); // support _method=PUT
        Route::put('modules/{module}', [ModuleController::class, 'update']);
        Route::delete('modules/{module}', [ModuleController::class, 'destroy']);
    });
});