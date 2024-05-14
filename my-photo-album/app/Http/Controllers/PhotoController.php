<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Photo;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PhotoController extends Controller
{
    public function show(Request $request, int $photoId)
    {
        $photo = Photo::findOrFail($photoId);

        return view(
            'single-photo',
            [
                'photo' => $photo
            ]
        );
    }

    public function share(Request $request, int $albumId, int $photoId)
    {
        $photo = Photo::find($photoId);

        if ($photo) {
            $photo->shared_at = Carbon::now();

            $photo->save();
        }

        return redirect('/albums/' . $albumId);
    }

    public function unshare(Request $request, int $albumId, int $photoId)
    {
        $photo = Photo::find($photoId);

        if ($photo) {
            $photo->shared_at = null;

            $photo->save();
        }

        return redirect('/albums/' . $albumId);
    }

    public function delete(Request $request, int $albumId)
    {
        $request->validateWithBag('photoDeletion', [
            'photo' => ['required'],
        ]);

        $album = Album::find($albumId);
        $photo = Photo::find($request->photo);

        $album->photos()->detach($photo->id);

        $count = $photo->inAnotherAlbum();

        if ($count === 0) {
            $photo->deleteFile();
            Photo::destroy($photo->id);
        }

        return redirect('/albums/' . $album->id);
    }
}
