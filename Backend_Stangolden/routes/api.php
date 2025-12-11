<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Api\ModuleController;
use App\Http\Controllers\Api\TryoutController;
use App\Http\Controllers\Api\FeedbackController;
use App\Http\Controllers\Api\AdminUserController;
use App\Http\Controllers\Api\ImportController;
use App\Http\Controllers\Api\QuestionBankController;
use App\Http\Controllers\Api\AttemptController;
use App\Http\Controllers\Api\ParticipantQuestionController;

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
    Route::get('/users', [UsersController::class, 'index']);

    Route::get('/admin/users', [AdminUserController::class, 'index']);
    Route::post('/admin/users/{user}', [AdminUserController::class, 'update']); // FE uses _method=PUT
    Route::post('/admin/users/{user}/approve', [AdminUserController::class, 'approve']);
    Route::post('/admin/users/{user}/revoke', [AdminUserController::class, 'revoke']);
    Route::post('/admin/users/{user}/toggle-active', [AdminUserController::class, 'toggleActive']);
    Route::post('/admin/users/{user}/expiry', [AdminUserController::class, 'setExpiry']);
    Route::delete('/admin/users/{user}', [AdminUserController::class, 'destroy']);

    // Feedback
    Route::get('feedbacks', [FeedbackController::class, 'index']);
    Route::post('feedbacks', [FeedbackController::class, 'store']);
    Route::get('feedbacks/{feedback}', [FeedbackController::class, 'show']);
    Route::put('feedbacks/{feedback}', [FeedbackController::class, 'update']);
    Route::post('feedbacks/{feedback}', [FeedbackController::class, 'update']); // support _method=PUT form-data
    Route::delete('feedbacks/{feedback}', [FeedbackController::class, 'destroy']);
    Route::patch('feedbacks/{feedback}/toggle', [FeedbackController::class, 'toggleResolved']);

    Route::get('banks/{bank}/questions', [ParticipantQuestionController::class, 'index']);

    // Attempt endpoints
    Route::post('attempts', [AttemptController::class, 'start']);
    Route::get('attempts/{attempt}', [AttemptController::class, 'show']);
    Route::post('attempts/{attempt}/finish', [AttemptController::class, 'finish']);
    Route::get('attempts/me', [AttemptController::class, 'myAttempts']);

    /*
    |----------------------------------------------------------------------------
    | Admin area
    |----------------------------------------------------------------------------
    */
    Route::prefix('admin')->group(function () {
        // Import
        Route::post('import/csv', [ImportController::class, 'importCsv']);
        Route::post('import/docx', [ImportController::class, 'importDocx']); // jika masih dipakai

        // Modules management (admin write)
        Route::get('modules', [ModuleController::class, 'index']);
        Route::get('modules/{module}', [ModuleController::class, 'show']);
        Route::post('modules', [ModuleController::class, 'store']);
        Route::post('modules/{module}', [ModuleController::class, 'update']); // support _method=PUT
        Route::put('modules/{module}', [ModuleController::class, 'update']);
        Route::delete('modules/{module}', [ModuleController::class, 'destroy']);

        // Tryout hierarchy (groups, series, sessions)
        Route::prefix('tryout')->group(function () {
            // Groups
            Route::get('groups', [TryoutController::class, 'listGroups']);
            Route::post('groups', [TryoutController::class, 'createGroup']);
            Route::put('groups/{group}', [TryoutController::class, 'updateGroup']);
            Route::delete('groups/{group}', [TryoutController::class, 'destroyGroup']);

            // Groups → Series
            Route::get('groups/{group}/series', [TryoutController::class, 'listSeries']);
            Route::post('groups/{group}/series', [TryoutController::class, 'createSeries']);

            // Series CRUD
            Route::get('series/{series}', [TryoutController::class, 'getSeries']); // optional detail
            Route::put('series/{series}', [TryoutController::class, 'updateSeries']);
            Route::delete('series/{series}', [TryoutController::class, 'destroySeries']);

            // Series → Sessions (IMPORTANT: use {series} to match implicit model binding)
            Route::get('series/{series}/sessions', [TryoutController::class, 'listSessions']);
            Route::post('series/{series}/sessions', [TryoutController::class, 'createSession']);

            // Sessions CRUD
            Route::put('sessions/{session}', [TryoutController::class, 'updateSession']);
            Route::delete('sessions/{session}', [TryoutController::class, 'destroySession']);

            // Generate questions for a session from bank
            Route::post('sessions/{session}/generate', [TryoutController::class, 'generateSessionQuestions']);
        });

        // Banks & Questions
        Route::get('banks', [QuestionBankController::class, 'index']);
        Route::get('admin/banks', [QuestionBankController::class, 'index']);
        Route::post('banks', [QuestionBankController::class, 'store']);
        Route::put('banks/{bank}', [QuestionBankController::class, 'update']);
        Route::delete('banks/{bank}', [QuestionBankController::class, 'destroy']);

        Route::get('banks/{bank}/questions', [QuestionBankController::class, 'listQuestions']);
        Route::post('banks/{bank}/questions', [QuestionBankController::class, 'storeQuestion']);
        Route::put('questions/{question}', [QuestionBankController::class, 'updateQuestion']);
        Route::delete('questions/{question}', [QuestionBankController::class, 'destroyQuestion']);
    });
});
