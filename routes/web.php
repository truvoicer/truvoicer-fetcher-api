<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Files\DownloadFileController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/download/file/{file_download:download_key}', DownloadFileController::class)->name('download.file');
