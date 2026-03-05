<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\FileManagerController;

Route::get('/', [DownloadController::class, 'index'])->name('home');
Route::post('/fetch', [DownloadController::class, 'fetch']);
Route::post('/download', [DownloadController::class, 'download']);
Route::get('/progress/{jobId}', [DownloadController::class, 'progress']);
Route::post('/cookies', [DownloadController::class, 'uploadCookies']);
Route::get('/cookies/check', [DownloadController::class, 'checkCookies']);
Route::get('/history', [DownloadController::class, 'history']);
Route::delete('/history', [DownloadController::class, 'clearHistory']);

Route::get('/files', [FileManagerController::class, 'index'])->name('files');
Route::get('/files/download', [FileManagerController::class, 'download']);
Route::post('/files/delete', [FileManagerController::class, 'delete']);
