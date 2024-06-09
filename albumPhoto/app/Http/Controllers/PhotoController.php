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

    public function getEncryptedKeys(Photo $photo)
{
    $owner = Auth::user();

    if ($photo->owner_id !== $owner->id) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }

    return response()->json([
        'encryptedKey' => $photo->encrypted_key,
        'encryptedIv' => $photo->encrypted_iv
    ]);
}

public function getPublicUserKey($email)
{
    $user = User::where('email', $email)->first();
    if ($user) {
        return response()->json(['publicKey' => $user->public_key_enc]);
    } else {
        return response()->json(['message' => 'User not found'], 404);
    }
}

public function share(Request $request, Photo $photo)
{
    $request->validate([
        'shareWith' => 'required|email'
    ]);

    $owner = Auth::user();
    $user = User::where('email', $request->shareWith)->first();
    $symmetricKey = $request->input('symmetric_key');
    $symmetricIv = $request->input('symmetric_iv');

    if ($user) {
        PhotoShared::addPhotoShared(
            $owner->id,
            $photo->id,
            $user->id,
            $symmetricKey,
            $symmetricIv
        );

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
        ->select('photos.*', 'photo_shared.symmetric_key', 'photo_shared.symmetric_iv', 'photo_shared.owner_id')
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
