<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;

class PhotoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'album_id' => 'required|exists:albums,id',
            'photo_name' => 'required|string|max:255',
            'photo' => 'required|image|max:2048',
        ]);

        $photoPath = $request->file('photo')->store('photos', 'public');

        Photo::create([
            'name' => $request->photo_name,
            'album_id' => $request->album_id,
            'path' => $photoPath,
        ]);

        return redirect()->route('gallery')->with('success', 'Photo uploaded successfully!');
    }
}
