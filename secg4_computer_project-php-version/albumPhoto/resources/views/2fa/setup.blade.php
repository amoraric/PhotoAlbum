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
                    <form method="POST" action="{{ route('2fa.setup.verify') }}">
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
                                <button type="submit" class="btn btn-primary">
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
