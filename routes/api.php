<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\StoryController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\Api\PinController;
use App\Http\Controllers\Api\VerifyEmailController;
use App\Http\Controllers\Api\PasswordResetController;







Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/create-story', [StoryController::class, 'store'])->middleware('auth:sanctum');
    Route::put('/stories/{id}', [StoryController::class, 'update'])->middleware('auth:sanctum'); // New update route
    Route::delete('/stories/{id}', [StoryController::class, 'destroy'])->middleware('auth:sanctum');
    Route::get('/stories', [StoryController::class, 'index'])->middleware('auth:sanctum');
    Route::get('/stories/{id}', [StoryController::class, 'show'])->middleware('auth:sanctum');
    Route::post('/upload', [UploadController::class, 'store'])->middleware('auth:sanctum');

    // Email & Password Routes
    Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
    Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    Route::post('/email/resend', [VerifyEmailController::class, 'resend'])->middleware('auth:sanctum');

    // Profile Settings
    Route::put('/profile', [AuthController::class, 'updateProfile'])->middleware('auth:sanctum');
    Route::put('/profile/password', [AuthController::class, 'updatePassword'])->middleware('auth:sanctum');
    Route::post('/profile/avatar', [AuthController::class, 'updateAvatar'])->middleware('auth:sanctum');
    Route::post('/profile/avatar-seed', [AuthController::class, 'updateAvatarSeed'])->middleware('auth:sanctum');

    // Children / Parents Area Routes
    Route::apiResource('children', \App\Http\Controllers\ChildController::class)->middleware('auth:sanctum');

    // PIN Management (Legacy - keeping for backward compatibility)
    Route::post('/pin', [AuthController::class, 'setPin'])->middleware('auth:sanctum');
    Route::post('/pin/verify', [AuthController::class, 'verifyPin'])->middleware('auth:sanctum');

    // New PIN Management Routes
    Route::post('/pin/change', [PinController::class, 'changePin'])->middleware('auth:sanctum');
    Route::post('/pin/verify-current', [PinController::class, 'verifyPin'])->middleware('auth:sanctum');

    // PIN Reset Routes (public - no auth required)
    Route::post('/pin/reset/request', [PinController::class, 'requestReset']);
    Route::post('/pin/reset/verify', [PinController::class, 'verifyResetAndSetPin']);

    // Menu Management




});

Route::get('/stories/public', [StoryController::class, 'publicIndex']);

Route::middleware('auth:sanctum')->get('/user', [AuthController::class, 'user']);

// Admin Routes
use App\Http\Controllers\Api\MenuController;
use App\Http\Controllers\Api\AdminStoryController;

// Public Menu Route (No auth required - items with no roles, visible to everyone)
Route::get('/menu/public', [MenuController::class, 'publicMenu']);

// User Menu Route (Authenticated Users - returns items based on user's roles)
Route::middleware('auth:sanctum')->get('/user/menu', [MenuController::class, 'myMenu']);

// Admin Protected Routes
Route::middleware(['auth:sanctum'])->prefix('admin')->group(function () {
    // Menu Management
    Route::apiResource('menus', MenuController::class);

    // Story Management & Credits
    Route::get('/stories', [AdminStoryController::class, 'index']);
    Route::get('/stories/{id}', [AdminStoryController::class, 'show']);
    Route::delete('/stories/{id}', [AdminStoryController::class, 'destroy']);

    Route::get('/users', [\App\Http\Controllers\AuthController::class, 'index']); // Assuming existing, else need to create
});

// Activity Logs (Admin Only)
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/activity-logs', [\App\Http\Controllers\Admin\ActivityLogController::class, 'index']);
    Route::get('/activity-logs/stats', [\App\Http\Controllers\Admin\ActivityLogController::class, 'stats']);
    Route::get('/activity-logs/{id}', [\App\Http\Controllers\Admin\ActivityLogController::class, 'show']);
    Route::post('/activity-logs/cleanup', [\App\Http\Controllers\Admin\ActivityLogController::class, 'cleanup']);
});

// AI Generation Routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/ai/story', [\App\Http\Controllers\Api\AiController::class, 'generateStory']);
    Route::post('/ai/image', [\App\Http\Controllers\Api\AiController::class, 'generateImage']);
});
