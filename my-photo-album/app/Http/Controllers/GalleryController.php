<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\Request;

class GalleryController extends Controller
{
    public function show(Request $request) {
        return view('gallery');
    }

    public function sharedPhotos(Request $request) {
        return response()->json(Photo::getShared());
    }
}
