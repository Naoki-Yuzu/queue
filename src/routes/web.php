<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Student\CsvBulkStoreController;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/login', [LoginController::class, '__invoke'])
    ->name('auth.login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/bulk-students/csv-upload', [CsvBulkStoreController::class, '__invoke'])
        ->name('bulk-students.csv-upload');
});
