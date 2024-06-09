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
use phpseclib3\Crypt\RSA;
use App\Models\PhotoShared;


class AlbumController extends Controller
{


    public function index()
{
    $userId = Auth::id(); // Get the currently authenticated user's ID
    $albums = Album::where('user_id', $userId)->with('photos')->get(); // Fetch albums belonging to the user

    foreach ($albums as $album) {
        foreach ($album->photos as $photo) {
            if (!$photo->encrypted_key || !$photo->encrypted_iv) {
                continue;
            }

            // Load the user's private key
            $privateKeyPath = storage_path('app/keys/' . $album->user->email . '.pem');
            $privateKeyContent = file_get_contents($privateKeyPath);
            $privateKey = PublicKeyLoader::loadPrivateKey($privateKeyContent);

            // Decrypt the AES key and IV
            $aesKey = $privateKey->decrypt(base64_decode($photo->encrypted_key));
            $aesIv = $privateKey->decrypt(base64_decode($photo->encrypted_iv));

            // Decrypt the photo content using AES
            $path = storage_path('app/public/' . $photo->filename);
            $encryptedContent = file_get_contents($path);
            $photoContent = file_get_contents($path); // Correctly load the photo content for signature verification

            // Load the user's public signing key
            $publicKey = PublicKeyLoader::load($album->user->public_key_sign);

            // Verify the signature
            $rsa = PublicKeyLoader::load($publicKey);
            $isSignatureValid = $rsa->verify($photoContent, base64_decode($photo->signature));

            if ($isSignatureValid) {
                $aes = new AES('cbc');
                $aes->setKey($aesKey);
                $aes->setIV($aesIv);
                $decryptedContent = $aes->decrypt($encryptedContent);

                if (!File::exists(storage_path('app/public/temp'))) {
                    File::makeDirectory(storage_path('app/public/temp'), 0755, true);
                }
                $tempPath = storage_path('app/public/temp/' . basename($photo->filename));
                file_put_contents($tempPath, $decryptedContent);

                $photo->temp_path = 'temp/' . basename($photo->filename);
            } else {
                // Handle invalid signature
                $photo->temp_path = null; // or some indication that the signature is invalid
            }
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

        // Retrieve shared albums for the user
        $sharedAlbums = Album::join('album_shared', 'albums.id', '=', 'album_shared.album_id')
            ->where('album_shared.shared_user_id', '=', $userId)
            ->select('albums.*')
            ->get();

        // Iterate through each shared album and decrypt its photos
        foreach ($sharedAlbums as $album) {
            foreach ($album->photos as $photo) {
                // Join the photo_shared table and retrieve the sharedEncrypted_key and sharedEncrypted_iv
                $photoShared = PhotoShared::where('photo_id', $photo->id)
                    ->where('shared_user_id', $userId)
                    ->select('sharedEncrypted_key', 'sharedEncrypted_iv')
                    ->first();

                if ($photoShared) {
                    // Decrypt the photo content using AES
                    $encryptedContent = file_get_contents(storage_path('app/public/' . $photo->filename));

                    // Retrieve the owner's public key
                    $owner = $album->user;
                    $ownerPublicKey = PublicKeyLoader::load($owner->public_key_sign);

                    // Decrypt the shared encrypted key and IV
                    $privateKeyPath = storage_path('app/keys/' . Auth::user()->email . '.pem'); // Assuming the logged-in user's private key
                    $privateKeyContent = file_get_contents($privateKeyPath);
                    $privateKey = PublicKeyLoader::loadPrivateKey($privateKeyContent);

                    $aesKey = $privateKey->decrypt(base64_decode($photoShared->sharedEncrypted_key));
                    $aesIv = $privateKey->decrypt(base64_decode($photoShared->sharedEncrypted_iv));

                    // Decrypt the photo content using AES
                    $aes = new AES('cbc');
                    $aes->setKey($aesKey);
                    $aes->setIV($aesIv);

                    // Verify the signature
                    $isSignatureValid = $ownerPublicKey->verify($encryptedContent, base64_decode($photo->signature));

                    if ($isSignatureValid) {
                        $decryptedContent = $aes->decrypt($encryptedContent);
                        // Save the decrypted content temporarily
                        if (!File::exists(storage_path('app/public/temp'))) {
                            File::makeDirectory(storage_path('app/public/temp'), 0755, true);
                        }
                        $tempPath = storage_path('app/public/temp/' . basename($photo->filename));
                        file_put_contents($tempPath, $decryptedContent);

                        $photo->temp_path = 'temp/' . basename($photo->filename); // Update the photo with the temporary path
                    }
                }
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

        $owner = Auth::user();
        $recipient = User::where('email', $request->shareWith)->first();

        if ($recipient) {
            // Load the owner's private key to decrypt the AES key and IV
            $ownerPrivateKeyPath = storage_path('app/keys/' . $owner->email . '.pem');
            $ownerPrivateKeyContent = file_get_contents($ownerPrivateKeyPath);
            $ownerPrivateKey = PublicKeyLoader::loadPrivateKey($ownerPrivateKeyContent);

            // Load the recipient's public key to re-encrypt the AES key and IV
            $recipientPublicKey = PublicKeyLoader::load($recipient->public_key_enc);

            // Store the shared album metadata
            $albumShared = new AlbumShared;
            $albumShared->owner_id = $owner->id;
            $albumShared->album_id = $album->id;
            $albumShared->shared_user_id = $recipient->id;
            $albumShared->save();

            // Iterate through each photo in the album
            foreach ($album->photos as $photo) {
                // Check if a shared entry already exists for this photo and recipient
                $existingSharedPhoto = PhotoShared::where('photo_id', $photo->id)
                    ->where('shared_user_id', $recipient->id)
                    ->exists();

                // If no existing entry found, create a new one
                if (!$existingSharedPhoto) {
                    // Decrypt the AES key and IV for the photo
                    $encryptedKey = base64_decode($photo->encrypted_key);
                    $encryptedIv = base64_decode($photo->encrypted_iv);
                    $aesKey = $ownerPrivateKey->decrypt($encryptedKey);
                    $aesIv = $ownerPrivateKey->decrypt($encryptedIv);

                    // Encrypt the AES key and IV with the recipient's public key
                    $newEncryptedKey = $recipientPublicKey->encrypt($aesKey);
                    $newEncryptedIv = $recipientPublicKey->encrypt($aesIv);

                    // Store the shared photo metadata
                    $photoShared = new PhotoShared;
                    $photoShared->owner_id = $owner->id;
                    $photoShared->photo_id = $photo->id;
                    $photoShared->shared_user_id = $recipient->id;
                    $photoShared->sharedEncrypted_key = base64_encode($newEncryptedKey);
                    $photoShared->sharedEncrypted_iv = base64_encode($newEncryptedIv);
                    $photoShared->save();
                }
            }

            return redirect()->route('gallery')->with('success', 'Album and photos shared successfully!');
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
