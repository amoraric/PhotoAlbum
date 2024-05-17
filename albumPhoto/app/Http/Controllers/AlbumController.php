<?php

// namespace App\Http\Controllers;

// use Illuminate\Http\Request;
// use App\Models\Album;

// class AlbumController extends Controller
// {
    // public function index()
    // {
    //     $albums = Album::with('photos')->get();
    //     return view('gallery', compact('albums'));
    // }

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
use Illuminate\Support\Facade\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Photo;
use App\Models\User;
use App\Models\AlbumShared;

class AlbumController extends Controller
{
    public function index()
    {
        $albums = Album::with('photos')->get();
        return view('gallery', compact('albums'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'album_name' => 'required|string|max:255',
        ]);

        $user_id = auth()->id();
        if (!$user_id) {
            return redirect()->route('gallery')->with('error', 'User not authenticated.');
        }

        Album::insertAlbum($request->album_name, $user_id);

        return redirect()->route('gallery')->with('success', 'Album created successfully!');
    }
    /*
    public function sharedAlbums()
    {
        $sharedAlbums = Album::where('is_shared', true)->with('photos')->get();
        return view('shared_albums', compact('sharedAlbums'));
    }
    
*/
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
        $this->authorize('update', $album);

        $user = User::where('email', $request->input('shareWith'))->first();
        if ($user && $album->users->contains($user->id)) {
            $album->users()->detach($user->id);
        }

        return redirect()->back()->with('status', 'Album unshared successfully!');
    }

    public function shareList(Album $album)
    {
        $this->authorize('view', $album);

        $shareList = $album->users()->get(['email']);
        return response()->json($shareList);
    }


}



