<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;

// Public controllers
use App\Http\Controllers\Api\ProjectController;
use App\Http\Controllers\Api\SkillController;
use App\Http\Controllers\Api\ExperienceController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\TagController;
use App\Http\Controllers\Api\ContactController;
use App\Http\Controllers\Api\SettingController;

// Admin controllers
use App\Http\Controllers\Admin\ProjectController as AdminProjectController;
use App\Http\Controllers\Admin\PostController as AdminPostController;
use App\Http\Controllers\Admin\SkillController as AdminSkillController;
use App\Http\Controllers\Admin\ExperienceController as AdminExperienceController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\TagController as AdminTagController;

// Health check endpoint
Route::get('/health', fn() => response()->json(['status' => 'ok']));


// Public routes
Route::get('/settings', [SettingController::class, 'index']);
Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/{slug}',  [ProjectController::class, 'show']);
Route::get('/skills',   [SkillController::class, 'index']);
Route::get('/experiences',  [ExperienceController::class, 'index']);
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/posts',    [PostController::class, 'index']);
Route::get('/posts/{slug}', [PostController::class, 'show']);
Route::get('/categories',   [CategoryController::class, 'index']);
Route::get('/tags', [TagController::class, 'index']);
Route::middleware('throttle:5,1')
    ->post('/contact',  [ContactController::class, 'store']);

// Admin Auth routes
Route::prefix('admin')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
    Route::get('/me', [AuthController::class, 'me'])->middleware('auth:sanctum');
});

// Admin protected routes
Route::prefix('admin')->middleware('auth:sanctum')->group(function () {

    // Projects
    Route::get('/projects',     [AdminProjectController::class, 'index']);
    Route::post('/projects',        [AdminProjectController::class, 'store']);
    Route::put('/projects/{project}',       [AdminProjectController::class, 'update']);
    Route::delete('/projects/{project}',        [AdminProjectController::class, 'destroy']);

    // Posts
    Route::get('/posts',             [AdminPostController::class, 'index']);
    Route::post('/posts',            [AdminPostController::class, 'store']);
    Route::put('/posts/{post}',      [AdminPostController::class, 'update']);
    Route::delete('/posts/{post}',   [AdminPostController::class, 'destroy']);

    //Skills
    Route::get('/skills',            [AdminSkillController::class, 'index']);
    Route::post('/skills',           [AdminSkillController::class, 'store']);
    Route::put('/skills/{skill}',    [AdminSkillController::class, 'update']);
    Route::delete('/skills/{skill}', [AdminSkillController::class, 'destroy']);

    //Experiences
    Route::get('/experiences',                   [AdminExperienceController::class, 'index']);
    Route::post('/experiences',                  [AdminExperienceController::class, 'store']);
    Route::put('/experiences/{experience}',      [AdminExperienceController::class, 'update']);
    Route::delete('/experiences/{experience}',   [AdminExperienceController::class, 'destroy']);
    // Services
    Route::get('/services',              [AdminServiceController::class, 'index']);
    Route::post('/services',             [AdminServiceController::class, 'store']);
    Route::put('/services/{service}',    [AdminServiceController::class, 'update']);
    Route::delete('/services/{service}', [AdminServiceController::class, 'destroy']);

    // Categories
    Route::get('/categories',                [AdminCategoryController::class, 'index']);
    Route::post('/categories',               [AdminCategoryController::class, 'store']);
    Route::put('/categories/{category}',     [AdminCategoryController::class, 'update']);
    Route::delete('/categories/{category}',  [AdminCategoryController::class, 'destroy']);

    // Tags
    Route::get('/tags',          [AdminTagController::class, 'index']);
    Route::post('/tags',         [AdminTagController::class, 'store']);
    Route::delete('/tags/{tag}', [AdminTagController::class, 'destroy']);

    // Settings
    Route::get('/settings',  [AdminSettingController::class, 'index']);
    Route::put('/settings',  [AdminSettingController::class, 'update']);

    // Messages
    Route::get('/messages',                      [MessageController::class, 'index']);
    Route::patch('/messages/{message}/read',     [MessageController::class, 'markRead']);
    Route::delete('/messages/{message}',         [MessageController::class, 'destroy']);

});
