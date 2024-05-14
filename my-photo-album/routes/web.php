<?php

use App\Http\Controllers\AlbumController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GalleryController;
use App\Http\Controllers\PhotoController;
use App\Http\Controllers\ProfileController;
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

Route::get('/', function () {
    return view('auth.login');
});

Route::get('/dashboard', [DashboardController::class, 'statistics'])
    ->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/albums', [AlbumController::class, 'collection'])
    ->middleware(['auth', 'verified'])->name('albums');

Route::post('/albums', [AlbumController::class, 'create'])
    ->middleware(['auth', 'verified'])->name('album.create');

Route::get('/albums/{album}', [AlbumController::class, 'single'])
    ->whereNumber('album')
    ->middleware(['auth', 'verified'])->name('album.read');

Route::post('/albums/{album}', [AlbumController::class, 'addPhotos'])
    ->whereNumber('album')
    ->middleware(['auth', 'verified'])->name('photos.add');

Route::patch('/albums/{album}', [AlbumController::class, 'update'])
    ->whereNumber('album')
    ->middleware(['auth', 'verified'])->name('album.update');

Route::delete('/albums/{album}', [AlbumController::class, 'delete'])
    ->whereNumber('album')
    ->middleware(['auth', 'verified'])->name('album.delete');

Route::get('/photos/{photo}', [PhotoController::class, 'show'])
    ->whereNumber('photo')
    ->middleware('auth', 'verified')->name('photo.show');

Route::get('/albums/{album}/photos/{photo}/share', [PhotoController::class, 'share'])
    ->whereNumber(['album', 'photo'])
    ->middleware('auth', 'verified')->name('photo.share');

Route::get('/albums/{album}/photos/{photo}/unshare', [PhotoController::class, 'unshare'])
    ->whereNumber(['album', 'photo'])
    ->middleware('auth', 'verified')->name('photo.unshare');

Route::delete('/photos/{photo}', [PhotoController::class, 'delete'])
    ->whereNumber('photo')
    ->middleware(['auth', 'verified'])->name('photo.delete');

Route::get('/gallery', [GalleryController::class, 'show'])
    ->middleware('auth', 'verified')->name('gallery');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
