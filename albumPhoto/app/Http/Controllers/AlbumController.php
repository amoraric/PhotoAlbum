<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use Illuminate\Support\Facade\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Photo;
use App\Models\User;
use App\Models\AlbumShared;

class AlbumController extends Controller
{
    public function index()
    {
        $userId = Auth::id(); // Get the currently authenticated user's ID
        $albums = Album::where('user_id', $userId)->with('photos')->get(); // Fetch albums belonging to the user

        return view('gallery', ['albums' => $albums]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'album_name' => 'required|string|max:255',
        ]);

        $userId = Auth::id();
        if (!$userId) {
            return redirect()->route('gallery')->with('error', 'User not authenticated.');
        }
        $albumName = $request->input('album_name');
        Album::insertAlbum($albumName, $userId);

        return redirect()->route('gallery')->with('success', 'Album created successfully!');
    }

    public function sharedAlbums(){
        $userId = Auth::id();
        $sharedAlbums = Album::join('album_shared', 'albums.id', '=', 'album_shared.album_id')
        ->where('album_shared.shared_user_id', '=', $userId) // Filtrer les albums partagés avec l'utilisateur connecté
        ->select('albums.*')
        ->get();
        return view('shared_albums', compact('sharedAlbums'));

    }

    public function createDefaultAlbum()
    {
        $userId = auth()->id();

        Album::insertALbum('gallery', $userId);
        return redirect()->route('home')->with('success', 'Default album created successfully!');
    }

    public function share(Request $request, Album $album)
    {
        $request->validate([
            'shareWith' => 'required|email'
        ]);

        $owner = Auth::id();
        $user = User::where('email', $request->shareWith)->first();
        if ($user) {
            AlbumShared::addAlbumShared($owner,$album->id,$user->id);
            return redirect()->route('gallery')->with('success', 'Photo shared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }

    public function unshare(Request $request, Album $album)
    {
        $request->validate([
            'unshareWith' => 'required|email'
        ]);
    
        $user = User::where('email', $request->unshareWith)->first();
        if ($user) {
            $album->sharedUsers()->detach($user->id);
            return redirect()->route('gallery')->with('success', 'Album unshared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }

    public function shareList(Album $album)
    {
        $this->authorize('view', $album);
        $sharedUsers = $album->sharedUsers()->get(['email']);
        return response()->json($sharedUsers);
    }
}
