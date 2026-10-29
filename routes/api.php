<?php

use App\Http\Controllers\Api\V1\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post(
        '/auth/register',
        [AuthController::class, 'register']
    )->middleware('throttle:5,1');
    Route::post(
        '/auth/login',
        [AuthController::class, 'login']
    )->middleware('throttle:5,1');
    Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    });
});
