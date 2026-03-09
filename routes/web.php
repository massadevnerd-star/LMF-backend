<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\VerifyEmailController;
use App\Http\Controllers\AuthController;

Route::prefix('api/auth')->group(function () {
    Route::get('/google/redirect', [AuthController::class, 'redirectToGoogle']);
    Route::get('/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', [VerifyEmailController::class, 'verify'])
    ->name('verification.verify');