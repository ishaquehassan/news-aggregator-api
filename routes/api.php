<?php

use App\Http\Controllers\API\Articles\ArticleController;
use App\Http\Controllers\API\Auth\ApiAuthController;
use App\Http\Controllers\API\UserPreferences\UserPreferenceController;
use Illuminate\Support\Facades\Route;

Route::controller(ApiAuthController::class)->prefix("auth")->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('forgot-password', 'getPasswordResetToken');
    Route::post('reset-password', 'resetPassword');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('user', 'getUser');
        Route::get('logout', 'logout');
        Route::put('change-password', 'changePassword');
        Route::put('update-profile', 'updateProfile');
    });
});

Route::apiResource('articles', ArticleController::class);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('preferences')->group(function () {
        Route::get('/', [UserPreferenceController::class, 'getPreferences']);
        Route::put('/', [UserPreferenceController::class, 'updatePreferences']);
        Route::get('/feed', [UserPreferenceController::class, 'getPersonalizedFeed']);
    });
});
