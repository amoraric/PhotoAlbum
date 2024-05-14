<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\PhotoController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/gallery', [AlbumController::class, 'index'])->name('gallery');

Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');