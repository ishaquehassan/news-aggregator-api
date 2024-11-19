<?php

use App\Http\Controllers\API\Articles\ArticleController;
use App\Http\Controllers\API\Auth\ApiAuthController;
use Illuminate\Support\Facades\Route;

Route::controller(ApiAuthController::class)->prefix("auth")->group(function(){
    Route::post('register', 'register');
    Route::post('login', 'login');
    Route::post('forgot-password', 'getPasswordResetToken');
    Route::post('reset-password', 'resetPassword');

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::get('user', 'getUser');
        Route::get('logout', 'logout');
        Route::post('change-password', 'changePassword');
        Route::post('update-profile', 'updateProfile');
    });
});

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('articles', ArticleController::class);
});

