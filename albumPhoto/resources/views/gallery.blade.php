@extends('layouts.app')
@section('content')
<div class="container">
    <h2>Create Album</h2>
    <form action="{{ route('albums.store') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="albumName">Album Name</label>
            <input type="text" class="form-control" id="albumName" name="album_name" required>
        </div>
        <button type="submit" class="btn btn-primary">Create Album</button>
    </form>

    <hr>

    <h2>Upload Photo</h2>
    <form id="photoForm" enctype="multipart/form-data">
        @csrf
        <div class="form-group">
            <label for="albumSelect">Select Album</label>
            <select class="form-control" id="albumSelect" name="album_id" required>
                @foreach($albums as $album)
                <option value="{{ $album->id }}">{{ $album->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-group">
            <label for="photoName">Photo Name</label>
            <input type="text" class="form-control" id="photoName" name="photo_name" required>
        </div>
        <div class="form-group">
            <label for="photoFile">Upload Photo</label>
            <input type="file" class="form-control-file" id="photoFile" name="photo" accept="image/*" required>
        </div>
        <button id="buttonUploadPhoto" class="btn btn-primary">Upload Photo</button>
    </form>




    <hr>

    <h2>Gallery</h2>
    <div class="row">
        @foreach($albums as $album)
        <div class="col-md-6">
            <h3>{{ $album->name }}</h3>
            <div class="row mb-2">
                <div class="col-12 text-end">
                    <button class="btn btn-secondary" type="button" data-bs-toggle="modal" data-bs-target="#shareModalAlbum" @click="setAlbumId({{ $album->id }})">Share Album</button>
                </div>
            </div>
            <div class="row">
                @foreach($album->photos as $photo)
                <div class="col-md-4 position-relative">
                    <img src="{{ asset('storage/' . $photo->temp_path) }}" alt="{{ $photo->photo_name }}" class="img-thumbnail" data-bs-toggle="modal" data-bs-target="#imageModal" @click="showImage('{{ asset('storage/' . $photo->temp_path) }}')">
                    <button class="btn btn-secondary position-absolute bottom-0 start-0 m-1" type="button" data-bs-toggle="modal" data-bs-target="#shareModalPhotos" @click="setPhotoId({{ $photo->id }})">Share Photo</button>
                </div>
                @endforeach
            </div>
        </div>
        @endforeach
    </div>
</div>

<!-- Image Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imageModalLabel">Image Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <img id="modalImage" src="" alt="Image" class="img-fluid">
            </div>
        </div>
    </div>
</div>

<!-- Share Modals -->
<share-modal-photos></share-modal-photos>
<share-modal-album></share-modal-album>

<script>
    $(document).ready(function() {
        console.log('hallo')

        $('#photoForm').on('submit', function(event) {
            event.preventDefault();

            let photoFile = $('#photoFile')[0].files[0];
            let albumId = $('#albumSelect').val();
            let photoName = $('#photoName').val();

            if (!photoFile || !albumId || !photoName) {
                alert("All fields are required.");
                return;
            }
            console.log("pre");

            // Read the file content
            let reader = new FileReader();
            reader.onload = async function(e) {
                let photoContent = e.target.result;
                console.log(1);
                // Generate AES key and IV (clé synchrone)
                let aesKey = await crypto.subtle.generateKey({
                        name: "AES-CBC",
                        length: 256
                    },
                    true,
                    ["encrypt", "decrypt"]
                );
                let aesIv = crypto.getRandomValues(new Uint8Array(16));

                // Encrypt the photo content
                let encryptedContent = await crypto.subtle.encrypt({
                        name: "AES-CBC",
                        iv: aesIv
                    },
                    aesKey,
                    photoContent
                );
                console.log(2);

                // Export AES key and IV for encryption
                let rawAesKey = await crypto.subtle.exportKey("raw", aesKey);
                let rawAesIv = new Uint8Array(aesIv.buffer); // ptet faut crypter ce truc aussi 
                console.log(3);

                // Encrypt AES key using your own public key
                // Assuming you have your own public key (in PEM format)
                let publicKeyPem = `{{$publicEncKey}}`;
                console.log(`{{$publicEncKey}}`);


                const publicKey = await crypto.subtle.importKey(
                    "spki",
                    pemToArrayBuffer(publicKeyPem), {
                        name: "RSA-OAEP",
                        hash: {
                            name: "SHA-256"
                        }
                    },
                    true,
                    ["encrypt"]
                );

                // Utilisation de la clé publique importée...

                console.log(5);

                let encryptedKey = await crypto.subtle.encrypt({
                        name: "RSA-OAEP"
                    },
                    publicKey,
                    rawAesKey
                );

                let encrypted_iv = await crypto.subtle.encrypt({
                        name: "RSA-OAEP"
                    },
                    publicKey,
                    rawAesIv
                );
                console.log(6);
                console.log("le form:")
                console.log('album_id =' + albumId)
                console.log('photo_name' + photoName)


                // Récupérer la clé privée de la signature à partir du stockage local
                let userEmail = "{{ session('email') }}"; // Récupérer l'email de l'utilisateur depuis la session
                let signPrivateKeyKeyName = userEmail + '_signPrivateKey';
                console.log("private = " + signPrivateKeyKeyName);
                let signPrivateKey = localStorage.getItem(signPrivateKeyKeyName);
                console.log(signPrivateKey);

                let privateKeyBuffer = Uint8Array.from(atob(signPrivateKey), c => c.charCodeAt(0)).buffer;

                // Charger la clé privée à partir du stockage local
                let privateKeyImported = await crypto.subtle.importKey(
                    "pkcs8", // Format de la clé : PKCS #8
                    privateKeyBuffer, {
                        name: "ECDSA",
                        namedCurve: "P-256"
                    },
                    true,
                    ["sign"]
                );

                // Signer le contenu chiffré de la photo
                let signature = await crypto.subtle.sign({
                        name: "ECDSA",
                        hash: {
                            name: "SHA-256"
                        } // Hash à utiliser pour la signature
                    },
                    privateKeyImported,
                    encryptedContent
                );

                // Préparer les données à envoyer
                let formData = new FormData();
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'));
                formData.append('photo', new Blob([encryptedContent]), photoFile.name);
                formData.append('album_id', albumId);
                formData.append('photo_name', photoName);
                formData.append('encrypted_key', btoa(String.fromCharCode.apply(null, new Uint8Array(rawAesKey))));
                formData.append('encrypted_iv', btoa(String.fromCharCode.apply(null, rawAesIv)));
                formData.append('signature', btoa(String.fromCharCode.apply(null, new Uint8Array(signature))));

                console.log(7)

                // Send the data to the server via AJAX
                $.ajax({
                    url: '{{route('photos.store')}}', // Your server endpoint to handle the upload
                    type: 'POST',
                    data: formData,
                    processData: false,
                    contentType: false,
                    success: function(response) {
                        alert('Photo uploaded successfully!');
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        console.log("fail")

                        alert('Failed to upload photo: ' + textStatus);
                    }
                });
                console.log(8);

            };
            console.log(9);

            reader.readAsArrayBuffer(photoFile);
            console.log(10);

        });
        console.log(11);

        
    });
    function pemToArrayBuffer(pem) {
            // Supprimer les en-têtes et pieds de page PEM
            let pemHeaderFooterRemoved = pem.replace(/-----BEGIN [^-]+-----|-----END [^-]+-----/g, '');
            // Supprimer les sauts de ligne
            let pemBody = pemHeaderFooterRemoved.replace(/\r?\n|\r/g, '');
            // Décoder la chaîne Base64 en une chaîne binaire
            let binaryString = atob(pemBody);
            // Créer un tableau d'octets à partir de la chaîne binaire
            let bytes = new Uint8Array(binaryString.length);
            for (let i = 0; i < binaryString.length; i++) {
                bytes[i] = binaryString.charCodeAt(i);
            }
            // Retourner le tableau d'octets (ArrayBuffer)
            return bytes.buffer;
        }
</script>
@endsection