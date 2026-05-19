<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\TalentController;
use App\Http\Controllers\MentorshipController;
use App\Http\Controllers\ConnectionController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;

// ── Public ────────────────────────────────────────────────────────────────────
Route::post('/register',[AuthController::class, 'register']);
Route::post('/login',[AuthController::class, 'login']);

// ── Authenticated ─────────────────────────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    // Profile
    Route::get('/me',[AuthController::class, 'me']);
    Route::post('/logout',[AuthController::class, 'logout']);
    Route::put('/profile',[AuthController::class, 'updateProfile']);
    Route::post('/profile/change-password',[AuthController::class, 'changePassword']);
    Route::post('/profile/photo',[AuthController::class, 'updatePhoto']);

    // Dashboard
    Route::get('/dashboard/stats',[DashboardController::class, 'stats']);

    // Talents (lookup)
    Route::get('/talents',[TalentController::class, 'index']);
    Route::post('/talents/attach',[TalentController::class, 'attachTalent']);
    Route::delete('/talents/{talent_id}',[TalentController::class, 'removeTalent']);
    Route::post('/talents/manage',[TalentController::class, 'storeLookup']);
    Route::put('/talents/manage/{id}',[TalentController::class, 'updateLookup']);
    Route::delete('/talents/manage/{id}',[TalentController::class, 'destroyLookup']);
    Route::get('/talent-directory',[TalentController::class, 'directory']);

    // Mentorships (legacy)
    Route::get('/mentors',[AuthController::class, 'getMentors']);
    Route::get('/mentorships/me',[MentorshipController::class, 'myMentorships']);
    Route::post('/mentorships/request',[MentorshipController::class, 'requestMentorship']);
    Route::post('/mentorships/{id}/respond',[MentorshipController::class, 'respondToRequest']);

    // ── Connections & Follow ─────────────────────────────────────────────────
    Route::get('/connections',[ConnectionController::class, 'index']);
    Route::get('/connections/pending',[ConnectionController::class, 'pendingRequests']);
    Route::post('/connections/request',[ConnectionController::class, 'request']);
    Route::post('/connections/{id}/respond',[ConnectionController::class, 'respond']);
    Route::delete('/connections/{userId}/disconnect',[ConnectionController::class, 'disconnect']);
    Route::post('/follow/{userId}',[ConnectionController::class, 'follow']);
    Route::delete('/follow/{userId}',[ConnectionController::class, 'unfollow']);
    Route::get('/followers',[ConnectionController::class, 'myFollowers']);
    Route::get('/network/discover',[ConnectionController::class, 'discover']);

    // ── Social Feed / Posts ──────────────────────────────────────────────────
    Route::get('/feed',[PostController::class, 'feed']);
    Route::post('/posts',[PostController::class, 'store']);
    Route::delete('/posts/{id}',[PostController::class, 'destroy']);
    Route::post('/posts/{id}/react',[PostController::class, 'react']);
    Route::get('/posts/{id}/comments',[PostController::class, 'getComments']);
    Route::post('/posts/{id}/comments',[PostController::class, 'addComment']);
    Route::delete('/posts/comments/{id}',[PostController::class, 'deleteComment']);
    Route::get('/users/{userId}/posts',[PostController::class, 'userPosts']);

    // ── Messaging ────────────────────────────────────────────────────────────
    Route::get('/conversations',[MessageController::class, 'conversations']);
    Route::get('/conversations/unread',[MessageController::class, 'totalUnread']);
    Route::get('/conversations/{connectionId}/messages',[MessageController::class, 'getMessages']);
    Route::post('/conversations/{connectionId}/messages',[MessageController::class, 'send']);

    // ── Notifications ────────────────────────────────────────────────────────
    Route::get('/notifications',[NotificationController::class, 'index']);
    Route::post('/notifications/{id}/read',[NotificationController::class, 'markAsRead']);
    Route::post('/notifications/read-all',[NotificationController::class, 'markAllAsRead']);

    // ── Projects ─────────────────────────────────────────────────────────────
    Route::get('/projects',[ProjectController::class, 'index']);
    Route::post('/projects',[ProjectController::class, 'store']);
    Route::get('/projects/{id}',[ProjectController::class, 'show']);
    Route::put('/projects/{id}',[ProjectController::class, 'update']);
    Route::delete('/projects/{id}',[ProjectController::class, 'destroy']);
    Route::post('/projects/{id}/updates',[ProjectController::class, 'addUpdate']);

    // ── Admin & User Management ───────────────────────────────────────────────
    Route::get('/users',[UserController::class, 'index']);
    Route::post('/users',[UserController::class, 'store']);
    Route::put('/users/{id}/status',[UserController::class, 'updateStatus']);
    Route::put('/users/{id}/role',[UserController::class, 'assignRole']);

    // Roles
    Route::get('/roles',[UserController::class, 'getRoles']);
    Route::post('/roles',[UserController::class, 'storeRole']);
    Route::put('/roles/{id}',[UserController::class, 'updateRole']);
    Route::delete('/roles/{id}',[UserController::class, 'destroyRole']);
    Route::put('/roles/{id}/permissions',[UserController::class, 'updateRolePermissions']);

    // Permissions
    Route::get('/permissions',[UserController::class, 'getPermissions']);
    Route::post('/permissions',[UserController::class, 'storePermission']);
    Route::put('/permissions/{id}',[UserController::class, 'updatePermission']);
    Route::delete('/permissions/{id}',[UserController::class, 'destroyPermission']);
});
