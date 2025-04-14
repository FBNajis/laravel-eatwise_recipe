<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RecipeController;

Route::middleware('api')->group(function () {
    // Auth routes
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/send-otp', [AuthController::class, 'sendOtp']);
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('/check-email', [AuthController::class, 'checkEmail']);
    Route::post('/reset-password', [AuthController::class, 'resetPassword']);

    // User routes
    Route::get('/user', [UserController::class, 'getUserProfile']);

    // Public recipe routes
    Route::get('/recipes/all', [RecipeController::class, 'allRecipes']);
    Route::get('/recipes/top', [RecipeController::class, 'topRecipes']);
    Route::get('/recipes/random', [RecipeController::class, 'randomRecipes']);
    Route::get('/recipes/category', [RecipeController::class, 'catRecipes']);
    Route::get('/recipes/budget', [RecipeController::class, 'budRecipes']);
    // Route for searching recipes
    Route::get('/recipes/search', [RecipeController::class, 'searchRecipes']); // Add this line

    // Authenticated routes
    Route::middleware('auth:sanctum')->group(function () {
        // Recipe CRUD operations
        Route::post('/recipes', [RecipeController::class, 'store']);
        Route::put('/recipes/{id}', [RecipeController::class, 'update']);
        Route::delete('/recipes/{id}', [RecipeController::class, 'destroy']);
        
        // User-specific recipe routes
        Route::get('/user/recipes', [RecipeController::class, 'userRecipes']);
        Route::get('/user/recipes/liked', [RecipeController::class, 'likedRecipes']);
        
        // Like/unlike routes
        Route::post('/recipes/{id}/like', [RecipeController::class, 'like']);
        Route::delete('/recipes/{id}/unlike', [RecipeController::class, 'unlike']);
        Route::get('/recipes/liked', [RecipeController::class, 'likedRecipes']);
    });
});
