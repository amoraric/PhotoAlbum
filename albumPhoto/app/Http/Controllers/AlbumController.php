<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\Album;

// class AlbumController extends Controller
// {
//     public function index()
//     {
//         $albums = Album::with('photos')->get();
//         return view('gallery', compact('albums'));
//     }

//     public function store(Request $request)
//     {
//         $request->validate([
//             'album_name' => 'required|string|max:255',
//         ]);

//         Album::create([
//             'name' => $request->album_name,
//             'user_id' => auth()->id(),
//         ]);

//         return redirect()->route('gallery')->with('success', 'Album created successfully!');
//     }
// }

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;

class AlbumController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'cover_image' => 'required|image',
            'title' => 'required|string'
        ]);

        $path = $request->file('cover_image')->store('albums', 'public');

        $album = new Album;
        $album->cover_image = $path;
        $album->title = $request->input('title');
        $album->save();

        return response()->json(['success' => true, 'path' => $path]);
    }
}
