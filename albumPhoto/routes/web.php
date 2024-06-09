<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AlbumController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\TwoFactorController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\KeyController;


Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::middleware(['auth', '2fa'])->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
    Route::get('/gallery', [AlbumController::class, 'index'])->name('gallery');
    
    Route::post('/albums', [AlbumController::class, 'store'])->name('albums.store');
    Route::post('/photos', [PhotoController::class, 'store'])->name('photos.store');
    Route::post('/photos/{photo}/share', [PhotoController::class, 'share'])->name('photos.share');
    Route::post('/photos/{photo}/unshare', [PhotoController::class, 'unshare'])->name('photos.unshare');
    
    Route::get('/photos/{photo}/share-list', [PhotoController::class, 'shareList'])->name('photos.shareList');
    Route::get('/shared-albums', [AlbumController::class, 'sharedAlbums'])->name('shared_albums');
    
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
    
    Route::get('/shared-photos',[PhotoController::class,'sharedPhotos'])->name('shared_photos');
    Route::get('/add-default-album', [AlbumController::class, 'createDefaultAlbum'])->name('addDefaultAlbum');
    Route::get('/sharealbum',[HomeController::class, 'index'])->name('sharealbum');
    
    Route::post('/albums/{album}/share', [AlbumController::class, 'share'])->name('albums.share');
    Route::post('/albums/{album}/unshare', [AlbumController::class, 'unshare'])->name('albums.unshare');
    
    Route::get('/albums/{album}/share-list', [AlbumController::class, 'shareList'])->name('albums.share-list');
  
  
    Route::get('2fa/enable', [UserController::class, 'enable2fa'])->name('2fa.enable');
  
    Route::post('2fa/disable', [UserController::class, 'disable2fa'])->name('2fa.disable');
  
    Route::get('2fa/verify', [TwoFactorController::class, 'showVerifyForm'])->name('2fa.verify');
  
    Route::post('2fa/verify', [TwoFactorController::class, 'verify'])->name('2fa.verify.post');
});

Route::post('register', [RegisterController::class, 'register'])->middleware('throttle:2,1');

// Route to show the 2FA setup form
Route::post('2fa/setup', [RegisterController::class, 'show2FAForm'])->name('2fa.setup.show');
Route::post('2fa/setup/verify', [RegisterController::class, 'verify2FA'])->name('2fa.setup.verify');

// New routes for storing keys
Route::post('keys/store', [KeyController::class, 'storeKeys'])->name('keys.store');

Route::get('/photos/decrypt/{id}', [PhotoController::class, 'decrypt'])->name('photos.decrypt');
