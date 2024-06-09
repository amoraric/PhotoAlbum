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
  // Validation des champs


// Récupération du fichier photo
$photoFile = $request->file('photo');
$photoContent = file_get_contents($photoFile->getPathname());

// Générer un nom de fichier unique
$filename = uniqid() . '.' . $photoFile->getClientOriginalExtension();
$path = storage_path('app/public/photos/' . $filename);

if (!File::exists(storage_path('app/public/photos'))) {
    File::makeDirectory(storage_path('app/public/photos'), 0755, true);
}

// Stocker le contenu chiffré de la photo
file_put_contents($path, $photoContent);

// Sauvegarder les métadonnées de la photo dans la base de données
$photo = new Photo;
$photo->filename = 'photos/' . $filename;
$photo->album_id = $request->album_id;
$photo->photo_name = $request->photo_name;
$photo->path = $path;
$photo->encrypted_key = $request->encrypted_key; // Stocker la clé AES chiffrée
$photo->encrypted_iv = $request->encrypted_iv; // Stocker l'IV chiffré
$photo->signature = $request->signature;

$photo->save();
}


// public function share(Request $request, Photo $photo)
//     {
//         $request->validate([
//             'shareWith' => 'required|email'
//         ]);

//         $owner = Auth::user();
//         $recipient = User::where('email', $request->shareWith)->first();

//         if ($recipient) {
//             // Load the owner's private key to decrypt the AES key and IV
//             $ownerPrivateKeyPath = storage_path('app/keys/' . $owner->email . '.pem');
//             $ownerPrivateKeyContent = file_get_contents($ownerPrivateKeyPath);
//             $ownerPrivateKey = PublicKeyLoader::loadPrivateKey($ownerPrivateKeyContent);

//             // Decrypt the AES key and IV
//             $encryptedKey = base64_decode($photo->encrypted_key);
//             $encryptedIv = base64_decode($photo->encrypted_iv);
//             $aesKey = $ownerPrivateKey->decrypt($encryptedKey);
//             $aesIv = $ownerPrivateKey->decrypt($encryptedIv);

//             // Load the recipient's public key to re-encrypt the AES key and IV
//             $recipientPublicKey = PublicKeyLoader::load($recipient->public_key_enc);

//             // Encrypt the AES key and IV with the recipient's public key
//             $newEncryptedKey = $recipientPublicKey->encrypt($aesKey);
//             $newEncryptedIv = $recipientPublicKey->encrypt($aesIv);

//             // Store the shared photo metadata
//             $photoShared = new PhotoShared;
//             $photoShared->owner_id = $owner->id;
//             $photoShared->photo_id = $photo->id;
//             $photoShared->shared_user_id = $recipient->id;
//             $photoShared->sharedEncrypted_key = base64_encode($newEncryptedKey);
//             $photoShared->sharedEncrypted_iv = base64_encode($newEncryptedIv);
//             $photoShared->save();

//             return redirect()->route('gallery')->with('success', 'Photo shared successfully!');
//         } else {
//             return redirect()->route('gallery')->with('error', 'User not found.');
//         }
//     }

    public function share(Request $request, Photo $photo)
    {
        $request->validate([
            'shareWith' => 'required|email'
        ]);
        $owner = Auth::id();
        $user = User::where('email', $request->shareWith)->first();
        $symmetric_key = $request->symmetric_key;
        $symmetric_iv = $request->symmetric_iv;
        if ($user) {
            PhotoShared::addPhotoShared([
                'owner_id' => $owner,
                'photo_id' => $photo->id,
                'shared_user_id' => $user->id,
                'symmetric_key' => $symmetric_key,
                'symmetric_iv' => $symmetric_iv
            ]);

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

    // Verify signatures and decrypt the photos
    foreach ($sharedImages as $photo) {
        $path = storage_path('app/public/' . $photo->filename);
        $encryptedContent = file_get_contents($path);

        $user = $photo->album->user;
        $privateKeyPath = storage_path('app/keys/' . $user->email . '.pem');
        $privateKeyContent = file_get_contents($privateKeyPath);
        $privateKey = PublicKeyLoader::load($privateKeyContent);

        // Decrypt AES key and IV
        $aesKey = $privateKey->decrypt(base64_decode($photo->encrypted_key));
        $aesIv = $privateKey->decrypt(base64_decode($photo->encrypted_iv));

        // Decrypt the photo content using AES


        // Verify the signature
        $photoContent = file_get_contents($path);
        $rsa=PublicKeyLoader::load($user->public_key_sign);
        $isSignatureValid = $rsa->verify($photoContent, base64_decode($photo->signature));

        if ($isSignatureValid) {
            $aes = new AES('cbc');
        $aes->setKey($aesKey);
        $aes->setIV($aesIv);
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

    public function decrypt($id)
    {
        $photo = Photo::findOrFail($id);

        $encryptedContent = file_get_contents(storage_path('app/public/' . $photo->filename));
        $encryptedKey = $photo->encrypted_key;
        $encryptedIv = $photo->encrypted_iv;
        $signature = $photo->signature;

        return response()->json([
            'encryptedContent' => base64_encode($encryptedContent),
            'encryptedKey' => $encryptedKey,
            'encryptedIv' => $encryptedIv,
            'signature' => $signature
        ]);
    }
}
