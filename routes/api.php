<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TopicController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\ReactionController;
use App\Http\Controllers\LikeController;
use App\Http\Controllers\FollowController;
use App\Http\Controllers\AppSettingsController;

Route::prefix('v1')->middleware(\App\Http\Middleware\CheckAppToken::class)->group(function () {
    // --- PUBLIC ROUTES ---
    // Authentication
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,1');
    Route::post('auth/send-otp', [AuthController::class, 'sendOtpToUser'])->middleware('throttle:5,1');
    Route::post('auth/verify-registration', [AuthController::class, 'verifyRegistration'])->middleware('throttle:5,1');
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
    Route::post('auth/google/login', [AuthController::class, 'loginWithGoogle'])->middleware('throttle:10,1');

    Route::get('auth/google', [AuthController::class, 'redirectToGoogle']);
    Route::post('auth/google/callback', [AuthController::class, 'handleGoogleCallback']);


    // Topics
    Route::get('topics', [TopicController::class, 'index']);

    // Posts
    Route::get('posts', [PostController::class, 'index']);
    Route::get('posts/{post}/reactions', [ReactionController::class, 'index']);
    Route::get('reactions/{reaction}/replies', [ReactionController::class, 'replies']);
    Route::get('posts/{id}', [PostController::class, 'getPostById']);

    Route::get('app-settings', [AppSettingsController::class, 'show']);


    // User Content & Info
    Route::get('users/{user}/posts', [PostController::class, 'userPosts']);
    Route::get('users/{user}/reactions', [ReactionController::class, 'userReactions']);
    Route::get('/users/{user}/followers', [UserController::class, 'followers']);
    Route::get('/users/{user}/followings', [UserController::class, 'followings']);

    Route::get('users/{user}', [UserController::class, 'show']);
    Route::post('auth/request-password-reset', [AuthController::class, 'requestPasswordReset'])->middleware('throttle:5,1');
    Route::post('auth/verify-reset-otp', [AuthController::class, 'verifyResetOtp'])->middleware('throttle:5,1');
    Route::post('auth/reset-password', [AuthController::class, 'resetPassword'])->middleware('throttle:5,1');

    // --- PROTECTED ROUTES (REQUIRE AUTHENTICATION) ---
    Route::middleware('auth:sanctum')->group(function () {
        // Authentication
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/user', [AuthController::class, 'user']);
        Route::post('user/update-profile', [AuthController::class, 'updateProfile']);

        // Account Deletion
        Route::post('user/request-delete-account', [AuthController::class, 'requestDeleteAccount']);
        Route::delete('user/delete-account', [AuthController::class, 'deleteAccount']);



        // Topics
        Route::post('topics', [TopicController::class, 'store']);

        // Posts
        Route::post('topics/{topic}/posts', [PostController::class, 'store']);
        Route::post('upload', [PostController::class, 'upload']);
        Route::delete('posts/{post}', [PostController::class, 'destroy'])->whereUuid('post');

        // Reactions
        Route::post('posts/{post}/reactions', [ReactionController::class, 'store']);
        Route::post('reactions/{reaction}/replies', [ReactionController::class, 'storeReply']);
        Route::delete('reactions/{reaction}', [ReactionController::class, 'destroy']);


        // Likes
        Route::post('posts/{post}/like', [LikeController::class, 'likePost']);
        Route::delete('posts/{post}/like', [LikeController::class, 'unlikePost']);
        Route::post('reactions/{reaction}/like', [LikeController::class, 'likeReaction']);
        Route::delete('reactions/{reaction}/like', [LikeController::class, 'unlikeReaction']);

        // Follow System
        Route::post('users/{user}/follow', [FollowController::class, 'follow']);
        Route::post('users/{user}/unfollow', [FollowController::class, 'unfollow']);

    });
});
