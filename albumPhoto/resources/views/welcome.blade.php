<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name', 'Photo Album') }}</title>

    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css?family=Nunito:200,600" rel="stylesheet">

    <!-- Styles -->
    <style>
        html, body {
            background-color: #f8fafc;
            color: #636b6f;
            font-family: 'Nunito', sans-serif;
            font-weight: 200;
            height: 100vh;
            margin: 0;
        }

        .full-height {
            height: 100vh;
        }

        .flex-center {
            align-items: center;
            display: flex;
            justify-content: center;
        }

        .position-ref {
            position: relative;
        }

        .top-right {
            position: absolute;
            right: 10px;
            top: 18px;
        }

        .content {
            text-align: center;
        }

        .title {
            font-size: 84px;
        }

        .buttons > a {
            color: #fff;
            padding: 10px 20px;
            font-size: 16px;
            font-weight: 600;
            text-decoration: none;
            text-transform: uppercase;
            border-radius: 5px;
            margin: 0 10px;
        }

        .btn-login {
            background-color: #3490dc;
        }

        .btn-register {
            background-color: #38c172;
        }

        .btn-login:hover, .btn-register:hover {
            opacity: 0.8;
        }
    </style>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    async function generateKeyPair() {
        const keyPairEnc = await crypto.subtle.generateKey(
            {
                name: "RSA-OAEP",
                modulusLength: 2048,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: "SHA-256"
            },
            true,
            ["encrypt", "decrypt"]
        );

        const keyPairSign = await crypto.subtle.generateKey(
            {
                name: "RSA-PSS",
                modulusLength: 2048,
                publicExponent: new Uint8Array([1, 0, 1]),
                hash: "SHA-256"
            },
            true,
            ["sign", "verify"]
        );

        const publicKeyEnc = await crypto.subtle.exportKey("spki", keyPairEnc.publicKey);
        const privateKeyEnc = await crypto.subtle.exportKey("pkcs8", keyPairEnc.privateKey);
        const publicKeySign = await crypto.subtle.exportKey("spki", keyPairSign.publicKey);
        const privateKeySign = await crypto.subtle.exportKey("pkcs8", keyPairSign.privateKey);

        // Store the private keys locally (e.g., local storage)
        localStorage.setItem('privateKeyEnc', arrayBufferToBase64(privateKeyEnc));
        localStorage.setItem('privateKeySign', arrayBufferToBase64(privateKeySign));

        return {
            publicKeyEnc: arrayBufferToBase64(publicKeyEnc),
            publicKeySign: arrayBufferToBase64(publicKeySign)
        };
    }

    function arrayBufferToBase64(buffer) {
        let binary = '';
        let bytes = new Uint8Array(buffer);
        let len = bytes.byteLength;
        for (let i = 0; i < len; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return window.btoa(binary);
    }

    $(document).ready(function() {
        $('#2faForm').on('submit', async function(event) {
            event.preventDefault();

            let otp = $('#one_time_password').val();
            $.ajax({
                url: '{{ route('2fa.setup.verify') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    one_time_password: otp
                },
                success: async function(response) {
                    if (response.valid) {
                        let keys = await generateKeyPair();

                        $.ajax({
                            url: '{{ route('keys.store') }}',
                            type: 'POST',
                            data: {
                                _token: '{{ csrf_token() }}',
                                email: response.email,
                                publicKeyEnc: keys.publicKeyEnc,
                                publicKeySign: keys.publicKeySign
                            },
                            success: function(storeResponse) {
                                window.location.href = '{{ route('gallery') }}';
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                alert('Failed to store keys: ' + textStatus);
                            }
                        });
                    } else {
                        alert('Invalid 2FA code.');
                    }
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert('2FA verification failed: ' + textStatus);
                }
            });
        });
    });
    </script>
</head>
<body>
    <div class="flex-center position-ref full-height">
        <div class="content">
            <div class="title m-b-md">
                Welcome
            </div>

            <div class="buttons">
                <a href="{{ route('login') }}" class="btn btn-login">Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="btn btn-register">Register</a>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
