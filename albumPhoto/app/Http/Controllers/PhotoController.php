<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Photo;
use App\Models\Album;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Models\PhotoShared;
use phpseclib3\Crypt\RSA;
use phpseclib3\Crypt\PublicKeyLoader;
use Illuminate\Support\Facades\Storage;
use phpseclib3\Crypt\AES;
use Illuminate\Support\Facades\File;



class PhotoController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'photo' => 'required|image',
            'album_id' => 'required|exists:albums,id', // Ensure album_id exists in albums table
            'photo_name' => 'required|string|max:255',
        ]);

        $photoFile = $request->file('photo');
        $photoContent = file_get_contents($photoFile->getPathname());

        // Generate a random AES key
        $aes = new AES('cbc');
        $aesKey = random_bytes(32); // 256-bit key for AES-256
        $aesIv = random_bytes(16); // 128-bit IV

        // Encrypt the photo content using AES
        $aes->setKey($aesKey);
        $aes->setIV($aesIv);
        $encryptedContent = $aes->encrypt($photoContent);



        // Load the recipient's public key
        $recipient = Auth::user();
        $publicKey = PublicKeyLoader::load($recipient->public_key);

        // Encrypt the AES key and IV using the recipient's public key
        $encryptedKey = $publicKey->encrypt($aesKey);
        $encryptedIv = $publicKey->encrypt($aesIv);

        // Generate a unique filename
        $filename = uniqid() . '.' . $photoFile->getClientOriginalExtension();
        $path = storage_path('app/public/photos/' . $filename);
     
        if (!File::exists(storage_path('app/public/photos'))) {
            File::makeDirectory(storage_path('app/public/photos'), 0755, true);
        } 

        // Store the encrypted photo content
        file_put_contents($path, $encryptedContent);

        // Save the photo metadata in the database
        $photo = new Photo;
        $photo->filename = 'photos/' . $filename;
        $photo->album_id = $request->album_id;
        $photo->photo_name = $request->photo_name;
        $photo->path = $path;
        $photo->encrypted_key = base64_encode($encryptedKey); // Store the encrypted AES key
        $photo->encrypted_iv = base64_encode($encryptedIv); // Store the encrypted AES IV
        $photo->save();

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
            PhotoShared::addPhotoShared($owner, $photo->id, $user->id);
            return redirect()->route('gallery')->with('success', 'Photo shared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }

    public function sharedPhotos()
    {
        $userId = Auth::id();
        $sharedImages = Photo::join('photo_shared', 'photos.id', '=', 'photo_shared.photo_id')
            ->where('photo_shared.shared_user_id', '=', $userId) // Filter photos shared with the logged-in user
            ->select('photos.*')
            ->get();

        // Decrypt the photos
        foreach ($sharedImages as $photo) {
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

    public function shareList(Photo $photo)
    {
        $this->authorize('view', $photo);
        $sharedUsers = $photo->sharedUsers()->get(['email']);
        return response()->json($sharedUsers);
    }
}
