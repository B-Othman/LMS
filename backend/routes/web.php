<?php

use App\Http\Controllers\SwaggerDocsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// API Documentation
Route::get('/api/docs', [SwaggerDocsController::class, 'ui'])->name('api.docs');
Route::get('/api/docs.json', [SwaggerDocsController::class, 'json'])->name('api.docs.json');
