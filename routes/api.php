<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Backend\UserController;
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
        Route::prefix('account')->name('account.')->group(function () {
            Route::post('/details', [AuthController::class, 'getAccountDetails'])->name('details');
            Route::post('/token/generate', [AuthController::class, 'newToken'])->name('token.generate');
        });
        Route::prefix('token')->name('token.')->group(function () {
            Route::get('/validate', [AuthController::class, 'validateToken'])->name('validate');
            Route::post('/user', [AuthController::class, 'getSingleUserByApiToken'])->name('user');
            Route::get('/login', [AuthController::class, 'accountTokenLogin'])->name('login');
        });
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/detail', [UserController::class, 'getSessionUserDetail'])->name('detail');
            Route::prefix('permission')->name('permission.')->group(function () {
                Route::get('/entity/list', [UserController::class, 'getProtectedEntitiesList'])->name('entity.list');
                Route::get('/entity/{entity}/{id}', [UserController::class, 'getUserEntityPermissionList'])->name('entity');
            });
            Route::prefix('api-token')->name('api-token.')->group(function () {
                Route::get('/{personalAccessToken}', [UserController::class, 'getSessionUserApiToken'])->name('detail');
                Route::get('/list', [UserController::class, 'getSessionUserApiTokenList'])->name('list');
            });
        });
    });
});
