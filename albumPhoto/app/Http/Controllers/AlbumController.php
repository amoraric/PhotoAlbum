<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Album;
use Illuminate\Support\Facade\DB;
use Illuminate\Support\Facades\Auth;
use App\Models\Photo;
use App\Models\User;
use App\Models\AlbumShared;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\AES;
use Illuminate\Support\Facades\File;

class AlbumController extends Controller
{


    public function index()
    {
        $userId = Auth::id(); // Get the currently authenticated user's ID
        $albums = Album::where('user_id', $userId)->with('photos')->get(); // Fetch albums belonging to the user
        // Decrypt the photos in each album
        foreach ($albums as $album) {
            foreach ($album->photos as $photo) {
                // Skip decryption if the photo doesn't have encrypted data
                if (!$photo->encrypted_key || !$photo->encrypted_iv) {
                    continue;
                }

                // Load the user's private key
                $privateKeyPath = storage_path('app/keys/' . $album->user->private_key_path);
                $privateKeyContent = file_get_contents($privateKeyPath);
                $privateKey = PublicKeyLoader::load($privateKeyContent);

                // Decrypt the AES key and IV
                $aesKey = $privateKey->decrypt(base64_decode($photo->encrypted_key));
                $aesIv = $privateKey->decrypt(base64_decode($photo->encrypted_iv));

                // Decrypt the photo content using AES
                $path = storage_path('app/public/' . $photo->filename);
                $encryptedContent = file_get_contents($path);

                if (!File::exists(storage_path('app/public/temp'))) {
                    File::makeDirectory(storage_path('app/public/temp'), 0755, true);
          } 
          

                $aes = new AES('cbc');
                $aes->setKey($aesKey);
                $aes->setIV($aesIv);
                $decryptedContent = $aes->decrypt($encryptedContent);

                // Save the decrypted content temporarily
                $tempPath = 'temp/' . basename($photo->filename);
                file_put_contents(storage_path('app/public/' . $tempPath), $decryptedContent);

                // Update the photo's temp_path
                $photo->temp_path = $tempPath;
            }
        }

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

    public function sharedAlbums()
{
    $userId = Auth::id();
    $sharedAlbums = Album::join('album_shared', 'albums.id', '=', 'album_shared.album_id')
        ->where('album_shared.shared_user_id', '=', $userId)
        ->select('albums.*')
        ->get();

    // Decrypt the photos in each shared album
    foreach ($sharedAlbums as $album) {
        foreach ($album->photos as $photo) {
            $path = storage_path('app/public/' . $photo->filename);
            $encryptedContent = file_get_contents($path);

            $user = $photo->album->user;
            $privateKeyPath = storage_path('app/keys/' . $user->private_key_path);
            $privateKeyContent = file_get_contents($privateKeyPath);
            $privateKey = PublicKeyLoader::load($privateKeyContent);

            $aesKey = $privateKey->decrypt(base64_decode($photo->encrypted_key));
            $aesIv = $privateKey->decrypt(base64_decode($photo->encrypted_iv));

            // Decrypt the photo content using AES
            $aes = new AES('cbc');
            $aes->setKey($aesKey);
            $aes->setIV($aesIv);
            $decryptedContent = $aes->decrypt($encryptedContent);

            // Save the decrypted content temporarily
            $tempPath = storage_path('app/public/temp/' . basename($photo->filename));
            file_put_contents($tempPath, $decryptedContent);

            $photo->temp_path = 'temp/' . basename($photo->filename); // Update the photo with the temporary path
        }
    }

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
