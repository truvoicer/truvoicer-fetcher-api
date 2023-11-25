<?php

use App\Http\Controllers\Api\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/
Route::prefix('auth')->name('auth.')->group(function () {
    Route::post('/login', [AuthController::class, 'login'])->name('login');
});

Route::middleware(['auth:sanctum', 'ability:api:public,api:admin,api:superuser,api:user'])->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::prefix('token')->name('token.')->group(function () {
            Route::get('/validate', [AuthController::class, 'validateToken'])->name('validate');
        });
    });
});
