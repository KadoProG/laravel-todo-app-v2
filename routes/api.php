<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\TaskActionController;
use App\Http\Controllers\v1\NotificationController;
use App\Http\Controllers\v1\TaskController;
use App\Http\Controllers\v1\UserController;
use App\Http\Controllers\v1\UserIconController;
use App\Http\Controllers\v1\UserMeTaskController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'v1'], function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    // <img> から直接参照するため認証を課さない
    Route::get('users/{user}/icon', [UserIconController::class, 'show'])->name('users.icon.show');
    Route::group(['middleware' => 'auth:api'], function () {
        Route::get('users/me', [UserController::class, 'me']);
        Route::post('users/{user}/icon', [UserIconController::class, 'store']);
        Route::delete('users/{user}/icon', [UserIconController::class, 'destroy']);
        Route::apiResource('users', UserController::class)->only(['index', 'update', 'destroy']);
        Route::apiResource('tasks', TaskController::class);
        Route::apiResource('tasks/{task}/actions', TaskActionController::class)
            ->names('tasks.actions');
        Route::apiResource('users/me/tasks', UserMeTaskController::class)
            ->only('index')
            ->names('users.me.tasks');
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::get('notifications/unread-count', [NotificationController::class, 'unreadCount']);
        Route::put('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::put('notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
    });
});
