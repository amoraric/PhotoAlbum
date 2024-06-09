<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Support\Facades\Auth;

class TwoFactorController extends Controller
{
    public function showVerifyForm()
    {
        return view('2fa.verify');
    }

    public function verify(Request $request)
    {
        $this->validate($request, [
            'one_time_password' => 'required',
        ]);

        $user = Auth::user();
        $google2fa = new Google2FA();

        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->one_time_password);

        if ($valid) {
            $user->is_2fa_authenticated = true;
            $user->save();
            return redirect()->route('gallery'); // Redirect to the gallery page
        } else {
            return redirect()->back()->withErrors(['one_time_password' => 'The provided 2FA code is invalid.']);
        }
    }
}
