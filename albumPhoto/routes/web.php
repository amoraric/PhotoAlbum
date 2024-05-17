<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\HomeController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/gallery', [AlbumController::class, 'index'])->name('gallery');
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');
    Route::post('/photos/{photo}/share', [PhotoController::class, 'share'])->name('photos.share');
    Route::post('/photos/{photo}/unshare', [PhotoController::class, 'unshare'])->name('photos.unshare');
    Route::get('/photos/{photo}/share-list', [PhotoController::class, 'shareList'])->name('photos.shareList');
    Route::get('/shared-albums', [AlbumController::class, 'sharedAlbums'])->name('shared_albums');
    Route::post('/logout', [App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');
    Route::get('/shared-photos',[PhotoController::class,'sharedPhotos'])->name('shared_photos');
    Route::get('/add-default-album', [AlbumController::class, 'createDefaultAlbum'])->name('addDefaultAlbum');
});