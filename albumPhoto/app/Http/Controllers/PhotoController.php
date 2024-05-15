<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\Photo;

// class PhotoController extends Controller
// {
//     public function store(Request $request)
//     {
//         $request->validate([
//             'album_id' => 'required|exists:albums,id',
//             'photo_name' => 'required|string|max:255',
//             'photo' => 'required|image|max:2048',
//         ]);

//         $photoPath = $request->file('photo')->store('photos', 'public');

//         Photo::create([
//             'name' => $request->photo_name,
//             'album_id' => $request->album_id,
//             'path' => $photoPath,
//         ]);

//         return redirect()->route('gallery')->with('success', 'Photo uploaded successfully!');
//     }
// }


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Album;
use App\Models\User;

class PhotoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required|image',
            'album_id' => 'required|exists:albums,id', // Ensure album_id exists in albums table
            'photo_name' => 'required|string|max:255',
        ]);

        $path = $request->file('photo')->store('photos', 'public');

        $photo = new Photo;
        $photo->filename = $path;
        $photo->album_id = $request->album_id;
        $photo->photo_name = $request->photo_name;
        $photo->name = $request->photo_name; // Optional: Set the 'name' field if necessary
        $photo->path = $path; // Set the 'path' field
        $photo->save();

        return response()->json(['success' => true, 'path' => $path]);
    }

    public function share(Request $request, Photo $photo)
    {
        $request->validate([
            'shareWith' => 'required|email'
        ]);

        $user = User::where('email', $request->shareWith)->first();
        if ($user) {
            $photo->sharedUsers()->attach($user->id);
            return redirect()->route('gallery')->with('success', 'Photo shared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }
}

