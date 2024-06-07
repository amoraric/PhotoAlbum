<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use PragmaRX\Google2FA\Google2FA;
use PragmaRX\Google2FAQRCode\Google2FA as Google2FAQRCode;
use App\Models\Album;
use Illuminate\Http\Request;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        // Custom validation rule to check against a blacklist of common passwords
        Validator::extend('not_common_password', function ($attribute, $value, $parameters, $validator) {
            $commonPasswords = [
                'password', '12345678', '123456789', '1234567890', 'qwerty', 'abc123',
                'password1', '12345', '1234', '123456', 'admin', 'letmein', 'welcome',
                'monkey', 'login', 'passw0rd'
            ];
            return !in_array($value, $commonPasswords);
        });

        return Validator::make($data, [
            'name' => ['required', 'string', 'max:50'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => [
                'required',
                'string',
                'confirmed',
                Password::min(8)
                    ->letters()
                    ->mixedCase()
                    ->numbers()
                    ->symbols()
                    ->uncompromised(), // Ensures the password has not been compromised in a data breach
            ],
        ]);
    }

    // Show 2FA setup form
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        $data = $request->all();
        $request->session()->put('user_data', $data);

        return $this->show2FAForm($request);
    }

    public function show2FAForm(Request $request)
    {
        $data = $request->session()->get('user_data');
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        $request->session()->put('google2fa_secret', $secret);

        $google2faQRCode = new Google2FAQRCode();
        $qrCodeUrl = $google2faQRCode->getQRCodeInline(
            config('app.name'),
            $data['email'],
            $secret
        );

        return view('2fa.setup', ['qrCodeUrl' => $qrCodeUrl, 'secret' => $secret]);
    }

    public function verify2FA(Request $request)
    {
        $this->validate($request, [
            'one_time_password' => 'required',
        ]);

        $google2fa = new Google2FA();
        $secret = $request->session()->get('google2fa_secret');

        $valid = $google2fa->verifyKey($secret, $request->one_time_password);

        if ($valid) {
            $data = $request->session()->get('user_data');
            $request->session()->forget(['user_data', 'google2fa_secret']);

            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'google2fa_secret' => $secret,
            ]);

            $request->session()->put('email', $user->email);

            return response()->json(['valid' => true, 'email' => $user->email]);
        } else {
            return response()->json(['valid' => false], 401);
        }
    }
}
