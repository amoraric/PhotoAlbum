<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;

class KeyController extends Controller
{
    public function storeKeys(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'publicKeyEnc' => 'required',
            'publicKeySign' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        $user->public_key_enc = $request->publicKeyEnc;
        $user->public_key_sign = $request->publicKeySign;
        $user->is_2fa_authenticated = true;
        $user->save();

        return response()->json(['success' => true]);
    }
}
