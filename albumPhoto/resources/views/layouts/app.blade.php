<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Photo Album') }}</title>

    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=Nunito" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">

    <!-- Scripts     <script src="{{ asset('js/security.js') }}" defer></script>
-->
    <!-- Bootstrap JS -->
    <script src="https://unpkg.com/openpgp@5.0.0/dist/openpgp.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    @vite(['resources/sass/app.scss', 'resources/js/app.js'])
</head>

<body>
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
                    let rawAesIv = new Uint8Array(aesIv.buffer);
                    console.log(3);

                    // Encrypt AES key using your own public key
                    // Assuming you have your own public key (in PEM format)
                    let publicKeyPem = `{{$publicKey}}`;
                    console.log(`{{$publicKey}}`);

                   


                        const publicKey = await crypto.subtle.importKey(
                            "spki", // Format de la clé
                            pemToArrayBuffer(publicKeyPem), // Clé au format ArrayBuffer
                            {
                                name: "ECDSA",
                                namedCurve: "P-256"
                            },
                            true,
                            ["verify"] // Opérations autorisées avec cette clé
                        );

                        // Utilisation de la clé publique importée...
                    
                    console.log(5);

                    let encryptedKey = await crypto.subtle.encrypt({
                            name: "ECDSA"
                        },
                        publicKey,
                        rawAesKey
                    );
                    console.log(6);

                    // Prepare the data to be sent
                    let formData = new FormData();
                    formData.append('photo', new Blob([encryptedContent]), photoFile.name);
                    formData.append('album_id', albumId);
                    formData.append('photo_name', photoName);
                    formData.append('encrypted_key', btoa(String.fromCharCode.apply(null, new Uint8Array(encryptedKey))));
                    formData.append('iv', btoa(String.fromCharCode.apply(null, rawAesIv)));
                    console.log(7)

                    // Send the data to the server via AJAX
                    $.ajax({
                        url: '{{ route('photos.store') }}', // Your server endpoint to handle the upload
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        success: function(response) {
                            alert('Photo uploaded successfully!');
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            alert('Failed to upload photo: ' + textStatus);
                        }
                    });
                    console.log(8);

                };
                reader.readAsArrayBuffer(photoFile);
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
        });
    </script>
    <div id="app">
        <nav class="navbar navbar-expand-md navbar-light bg-primary shadow-sm">
            <div class="container">
                <a class="navbar-brand text-white" href="{{ url('/') }}">
                    {{ config('app.name', 'Photo Album') }}
                </a>
                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="{{ __('Toggle navigation') }}">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('gallery') }}">Gallery</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('shared_albums') }}">Shared Albums</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('shared_photos') }}">Shared Photos</a>
                        </li>
                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                        @if (Route::has('login'))
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('login') }}">{{ __('Login') }}</a>
                        </li>
                        @endif

                        @if (Route::has('register'))
                        <li class="nav-item">
                            <a class="nav-link text-white" href="{{ route('register') }}">{{ __('Register') }}</a>
                        </li>
                        @endif
                        @else
                        <li class="nav-item dropdown">
                            <a id="navbarDropdown" class="nav-link dropdown-toggle text-white" href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>

                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    {{ __('Logout') }}
                                </a>

                                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                    @csrf
                                </form>
                            </div>
                        </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>

        <main class="py-4">
            @yield('content')
            @yield('script')
        </main>

    </div>


</body>

</html>