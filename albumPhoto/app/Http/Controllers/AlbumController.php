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

    public function sharedAlbums()
    {
        $sharedAlbums = Album::where('is_shared', true)->with('photos')->get();
        return view('shared_albums', compact('sharedAlbums'));
    }

    public function createDefaultAlbum()
    {
        $userId = auth()->id();

        /*
        DB::statement('INSERT INTO albums (name, user_id, created_at, updated_at) VALUES (?, ?, ?, ?)', [
            'ggggg',
            $userId,
            now(),
            now(),
        ]);
*/
        Album::insertALbum('gallery', $userId);
        return redirect()->route('home')->with('success', 'Default album created successfully!');
    }

    
}



