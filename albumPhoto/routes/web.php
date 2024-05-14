<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index']);

Route::get('/gallery', [AlbumController::class, 'index']);

Route::post('/albums', [AlbumController::class, 'store']);
Route::post('/photos', [PhotoController::class, 'store']);

Route::post('/photos/add', [PhotoController::class, 'store']);
Route::post('/albums/add', [AlbumController::class, 'store']);
