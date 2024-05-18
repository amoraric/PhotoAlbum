@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('login') }}" id="login-form">
                        @csrf

                        @if(session('lockout_time'))
                            <div class="alert alert-danger">
                                <strong>{{ __('Too many login attempts. Please try again in :seconds seconds.', ['seconds' => session('lockout_time')]) }}</strong>
                            </div>
                        @endif

                        <div class="form-group row">
                            <label for="email" class="col-md-4 col-form-label text-md-right">{{ __('E-Mail Address') }}</label>

                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus @if(session('lockout_time')) disabled @endif>

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group row">
                            <label for="password" class="col-md-4 col-form-label text-md-right">{{ __('Password') }}</label>

                            <div class="col-md-6">
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required autocomplete="current-password" @if(session('lockout_time')) disabled @endif>

                                @error('password')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>

                        @if(session('2fa'))
                        <div class="form-group row">
                            <label for="2fa_code" class="col-md-4 col-form-label text-md-right">{{ __('2FA Code') }}</label>

                            <div class="col-md-6">
                                <input id="2fa_code" type="text" class="form-control @error('2fa_code') is-invalid @enderror" name="2fa_code" required>

                                @error('2fa_code')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        @endif

                        <div class="form-group row">
                            <div class="col-md-6 offset-md-4">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }} @if(session('lockout_time')) disabled @endif>

                                    <label class="form-check-label" for="remember">
                                        {{ __('Remember Me') }}
                                    </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="col-md-8 offset-md-4">
                                <button type="submit" class="btn btn-primary" id="login-button" @if(session('lockout_time')) disabled @endif>
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif

                                <a class="btn btn-link" href="{{ route('register') }}">
                                    {{ __('Register') }}
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

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if (session('lockout_time'))
            var lockoutTime = {{ session('lockout_time') }};
            var interval = setInterval(function() {
                lockoutTime--;
                document.getElementById('lockoutTime').innerText = lockoutTime;
                if (lockoutTime <= 0) {
                    clearInterval(interval);
                    location.reload();
                }
            }, 1000);
        @endif
    });
</script>
@endsection
