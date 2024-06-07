@extends('layouts.app')

@section('content')
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
        $('#verifyId').click(async function(event) {
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
                    <form id="2faForm">
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
                                <button id="verifyId" onclick="generateKeyPair()" type="submit" class="btn btn-primary">
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
