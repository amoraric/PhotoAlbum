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
use Illuminate\Support\Facades\Auth;
use App\Models\PhotoShared;

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
        //$photo->name = $request->photo_name; // Optional: Set the 'name' field if necessary
        $photo->path = $path; // Set the 'path' field
        $photo->save();

     //   return response()->json(['success' => true, 'path' => $path]);
        return redirect()->back();
      }

    public function share(Request $request, Photo $photo)
    {
        $request->validate([
            'shareWith' => 'required|email'
        ]);
        $owner = Auth::id();
        $user = User::where('email', $request->shareWith)->first();
        if ($user) {
            PhotoShared::addPhotoShared($owner,$photo->id,$user->id);
            return redirect()->route('gallery')->with('success', 'Photo shared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }

    public function sharedPhotos(){
        $userId = Auth::id();
        $sharedImages = Photo::join('photo_user', 'photos.id', '=', 'photo_user.photo_id')
        ->where('photo_user.user_id', '=', $userId) // Filtrer les photos partagées avec l'utilisateur connecté
        ->select('photos.*')
        ->get();
        return view('shared_photos', compact('sharedImages'));
    }

    public function unshare(Request $request, Photo $photo)
    {
        $request->validate([
            'unshareWith' => 'required|email'
        ]);

        $user = User::where('email', $request->unshareWith)->first();
        if ($user) {
            $photo->sharedUsers()->detach($user->id);
            return redirect()->route('gallery')->with('success', 'Photo unshared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }
}

