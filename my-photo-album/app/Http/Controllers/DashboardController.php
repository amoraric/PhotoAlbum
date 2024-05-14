<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function statistics(Request $request)
    {
        $user = $request->user();

        $albums = Album::countMyAlbums($user->id);

        $photos = Photo::countAllByUser($user->id);

        return view(
            'dashboard',
            [
                'user' => $user,
                'countAlbum' => $albums,
                'countPhoto' => $photos
            ]
        );
    }
}
