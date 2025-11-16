<?php

use App\Http\Controllers\Api\FileImportController;
use App\Http\Controllers\Api\SearchImportedDataController;
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

Route::middleware('jwt.auth')->prefix('uploads')->group(function () {
    Route::post('/', [FileImportController::class, 'upload'])->name('uploads.upload');
    Route::get('/', [FileImportController::class, 'history'])->name('uploads.history');
    Route::get('/{uploadHistoric}', [FileImportController::class, 'show'])->name('uploads.show');
});

Route::middleware('jwt.auth')->get('/data', [SearchImportedDataController::class, 'getData'])->name('data.search');
