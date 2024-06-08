@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Two-Factor Authentication Setup') }}</div>

                <div class="card-body">
                    <p>To set up two-factor authentication, scan the following QR code with your Google Authenticator app or enter the secret key manually.</p>
                    <div class="text-center">
                        {!! $qrCodeUrl !!}
                    </div>
                    <p class="text-center mt-3">Or enter this secret key manually: <strong>{{ $secret }}</strong></p>
                    <form id="2fa-setup-form">
                        @csrf

                        <div class="form-group row mt-3">
                            <label for="one_time_password" class="col-md-4 col-form-label text-md-right">{{ __('2FA Code') }}</label>

                            <div class="col-md-6">
                                <input id="one_time_password" type="text" class="form-control @error('one_time_password') is-invalid @enderror" name="one_time_password" required autofocus>

                                @error('one_time_password')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="button" id="verify-btn" class="btn btn-primary">
                                    {{ __('Verify') }}
                                </button>
                                <a class="btn btn-link" href="{{ route('register') }}">
                                    {{ __('Cancel') }}
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('verify-btn').addEventListener('click', async function() {
            console.log('Verify button clicked');
            var oneTimePassword = document.getElementById('one_time_password').value;
            var token = document.querySelector('input[name="_token"]').value;

            console.log('Generating keys...');
            try {
                // Generate key pairs using Web Crypto API
                const encKeyPair = await generateEncryptionKeyPair();
                const signKeyPair = await generateKeyPair();

                const encPublicKey = await exportPublicKey(encKeyPair.publicKey);
                const signPublicKey = await exportPublicKey(signKeyPair.publicKey);
                const encPrivateKey = await exportPrivateKey(encKeyPair.privateKey);
                const signPrivateKey = await exportPrivateKey(signKeyPair.privateKey);

                // Store private keys locally


                console.log('Keys generated and private keys stored locally, sending request...');

                fetch('{{ route('2fa.setup.verify')}}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': token
                            },
                            body: JSON.stringify({
                                one_time_password: oneTimePassword,
                                enc_public_key: encPublicKey,
                                sign_public_key: signPublicKey,
                                _token: token
                            })
                        })
                    .then(response => {
                        if (!response.ok) {
                            return response.text().then(text => {
                                throw new Error(text);
                            });
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            console.log('Verification successful, redirecting...');
                            window.location.href = data.redirect_url;
                        } else {
                            console.error('Verification failed:', data.message || 'An error occurred');
                            displayError(data.message || 'An error occurred');
                        }
                    })
                    .catch(error => {
                        console.error('There was a problem with the fetch operation:', error);
                        displayError('An error occurred: ' + error.message);
                    });





                // Store the private keys in the local storage
                localStorage.setItem('encPrivateKey', encPrivateKey);
                localStorage.setItem('signPrivateKey', signPrivateKey);

                // Optionally, you can set permissions (if applicable) or encrypt the keys before storing them
                // Ensure that sensitive data is handled securely and following best practices

                // Console log to confirm
                console.log('Private keys stored locally for ' + email);
            } catch (error) {
                console.error('Key generation failed:', error);
                displayError('Key generation failed: ' + error.message);
            }

            function displayError(message) {
                let errorSpan = document.querySelector('#one_time_password + .invalid-feedback');
                if (!errorSpan) {
                    errorSpan = document.createElement('span');
                    errorSpan.className = 'invalid-feedback';
                    errorSpan.role = 'alert';
                    document.getElementById('one_time_password').parentNode.appendChild(errorSpan);
                }
                errorSpan.innerHTML = '<strong>' + message + '</strong>';
                document.getElementById('one_time_password').classList.add('is-invalid');
            }

            async function generateKeyPair() {
                return crypto.subtle.generateKey({
                        name: "ECDSA",
                        namedCurve: "P-256"
                    },
                    true,
                    ["sign", "verify"]
                );
            }

            async function generateEncryptionKeyPair() {
                const keyPair = await crypto.subtle.generateKey({
                    name: "RSA-OAEP",
                    modulusLength: 2048,
                    publicExponent: new Uint8Array([0x01, 0x00, 0x01]), // Equivalent to 65537
                    hash: {
                        name: "SHA-256"
                    },
                }, true, ["encrypt", "decrypt"]);

                return keyPair;
            }
            async function exportPublicKey(publicKey) {
                const exported = await crypto.subtle.exportKey(
                    "spki",
                    publicKey
                );
                return arrayBufferToBase64(exported);
            }

            async function exportPrivateKey(privateKey) {
                const exported = await crypto.subtle.exportKey(
                    "pkcs8",
                    privateKey
                );
                return arrayBufferToBase64(exported);
            }

            function arrayBufferToBase64(buffer) {
                let binary = '';
                const bytes = new Uint8Array(buffer);
                const len = bytes.byteLength;
                for (let i = 0; i < len; i++) {
                    binary += String.fromCharCode(bytes[i]);
                }
                return window.btoa(binary);
            }
        });
    });
</script>
@endsection