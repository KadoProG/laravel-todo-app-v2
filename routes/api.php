<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\v1\TaskController;
use App\Http\Controllers\v1\UserController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('users/me', [UserController::class, 'me']);
        Route::get('users', [UserController::class, 'index']);
        Route::apiResource('/tasks', TaskController::class);
    });
});
