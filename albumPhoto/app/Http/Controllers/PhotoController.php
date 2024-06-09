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
    $publicKey = PublicKeyLoader::load($recipient->public_key_enc);

    // Encrypt the AES key and IV using the recipient's public key
    $encryptedKey = $publicKey->encrypt($aesKey);
    $encryptedIv = $publicKey->encrypt($aesIv);

    // Load the user's private signing key
    $privateKeyPath = storage_path('app/keys/' . $recipient->email . '.sign.pem');
    $privateKeyContent = file_get_contents($privateKeyPath);
    $privateKey = PublicKeyLoader::loadPrivateKey($privateKeyContent);

    // Sign the photo content
    $signature = $privateKey->sign($encryptedContent);

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
    $photo->signature = base64_encode($signature); // Store the digital signature
    $photo->save();

    return redirect()->back();
}




    public function share(Request $request, Photo $photo)
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
            $existingSharedPhoto = PhotoShared::where('photo_id', $photo->id)
                    ->where('shared_user_id', $recipient->id)
                    ->exists();
                    $recipientPublicKey = PublicKeyLoader::load($recipient->public_key_enc);
            // Decrypt the AES key and IV

                // Decrypt the AES key and IV for the photo
                $encryptedKey = base64_decode($photo->encrypted_key);
                $encryptedIv = base64_decode($photo->encrypted_iv);
                $aesKey = $ownerPrivateKey->decrypt($encryptedKey);
                $aesIv = $ownerPrivateKey->decrypt($encryptedIv);

                // Encrypt the AES key and IV with the recipient's public key
                $newEncryptedKey = $recipientPublicKey->encrypt($aesKey);
                $newEncryptedIv = $recipientPublicKey->encrypt($aesIv);
                if (!$existingSharedPhoto) {
                // Store the shared photo metadata
                $photoShared = new PhotoShared;
                $photoShared->owner_id = $owner->id;
                $photoShared->photo_id = $photo->id;
                $photoShared->shared_user_id = $recipient->id;
                $photoShared->sharedEncrypted_key = base64_encode($newEncryptedKey);
                $photoShared->sharedEncrypted_iv = base64_encode($newEncryptedIv);
                $photoShared->save();
                }


            return redirect()->route('gallery')->with('success', 'Photo shared successfully!');
        } else {
            return redirect()->route('gallery')->with('error', 'User not found.');
        }
    }

    public function sharedPhotos()
{
    $userId = Auth::id();

    // Join the photos and photo_shared tables to get the necessary fields
    $sharedImages = Photo::join('photo_shared', 'photos.id', '=', 'photo_shared.photo_id')
        ->where('photo_shared.shared_user_id', '=', $userId)
        ->select('photos.*', 'photo_shared.sharedEncrypted_key', 'photo_shared.sharedEncrypted_iv', 'photo_shared.owner_id')
        ->get();

    // Verify signatures and decrypt the photos
    foreach ($sharedImages as $photo) {
        $path = storage_path('app/public/' . $photo->filename);
        $encryptedContent = file_get_contents($path);

        // Retrieve the owner's public key
        $owner = User::find($photo->owner_id);
        $ownerPublicKey = PublicKeyLoader::load($owner->public_key_sign);

        // Decrypt the shared encrypted key and IV
        $privateKeyPath = storage_path('app/keys/' . Auth::user()->email . '.pem'); // Assuming the logged-in user's private key
        $privateKeyContent = file_get_contents($privateKeyPath);
        $privateKey = PublicKeyLoader::load($privateKeyContent);

        $aesKey = $privateKey->decrypt(base64_decode($photo->sharedEncrypted_key));
        $aesIv = $privateKey->decrypt(base64_decode($photo->sharedEncrypted_iv));

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
