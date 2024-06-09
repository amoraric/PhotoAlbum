<?php

use PragmaRX\Google2FALaravel\Support\Authenticator;
use PragmaRX\Google2FALaravel\Google2FA;

class UserController extends Controller
{
    public function enable2fa(Request $request)
    {
        $user = auth()->user();
        $google2fa = app('pragmarx.google2fa');

        // Generate a new secret key
        $user->google2fa_secret = $google2fa->generateSecretKey();
        $user->save();

        // Generate the QR code
        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $user->google2fa_secret
        );
        return view('2fa.enable', ['qrCodeUrl' => $qrCodeUrl, 'secret' => $user->google2fa_secret]);
    }

    public function disable2fa(Request $request)
    {
        $user = auth()->user();
        $user->google2fa_secret = null;
        $user->save();

        return redirect()->route('profile')->with('status', '2FA has been disabled.');
    }
}
