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

    /**
     * Handle a registration request for the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $this->validator($request->all())->validate();

        // Redirect to 2FA setup form
        return $this->show2FAForm($request);
    }

    // Show 2FA setup form
    public function show2FAForm(Request $request)
    {
        $data = $request->all();
        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();

        // Store user data and 2FA secret in session
        $request->session()->put('user_data', $data);
        $request->session()->put('google2fa_secret', $secret);

        // Generate QR code URL using Google2FAQRCode
        $google2faQRCode = new Google2FAQRCode();
        $qrCodeUrl = $google2faQRCode->getQRCodeInline(
            config('app.name'),
            $data['email'],
            $secret
        );

        return view('2fa.setup', ['qrCodeUrl' => $qrCodeUrl, 'email'=> $data['email'],'secret' => $secret]);
    }

    public function verify2FA(Request $request)
    {
        try {
            $this->validate($request, [
                'one_time_password' => 'required',
                'enc_public_key' => 'required',
                'sign_public_key' => 'required'
            ]);

            $google2fa = new Google2FA();
            $secret = $request->session()->get('google2fa_secret');

            $valid = $google2fa->verifyKey($secret, $request->one_time_password);

            if ($valid) {
                $data = $request->session()->get('user_data');

                $user = User::create([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => Hash::make($data['password']),
                    'google2fa_secret' => $secret,
                    'public_key_enc' => $request->enc_public_key,
                    'public_key_sign' => $request->sign_public_key
                ]);

                $user->is_2fa_authenticated = true;
                $user->save();

                $request->session()->forget(['user_data', 'google2fa_secret']);
                $this->guard()->login($user);
                $request->session()->put('email', $user->email);

            
                Album::insertAlbum('gallery', $user->id);

                return response()->json(['success' => true, 'redirect_url' => $this->redirectPath()]);
            } else {
                return response()->json(['success' => false, 'message' => 'The provided 2FA code is invalid.'], 422);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }




    // Verify 2FA code and create user
    // public function verify2FA(Request $request)
    // {
    //     try {
    //         $this->validate($request, [
    //             'one_time_password' => 'required',
    //         ]);
    
    //         $google2fa = new Google2FA();
    //         $secret = $request->session()->get('google2fa_secret');
    
    //         $valid = $google2fa->verifyKey($secret, $request->one_time_password);
    
    //         if ($valid) {
    //             $data = $request->session()->get('user_data');
    //             $EncKeyPair = User::generateKeyPair();
    //             $SignKeyPair = User::generateKeyPair();
    
    //             $privateKeyDir = storage_path('app/keys');
    //             $EncprivateKeyFileName = $data['email'] . '.pem';
    //             $SignprivateKeyFileName = $data['email'] . '.sign.pem';
    //             $EncprivateKeyPath = $privateKeyDir . '/' . $EncprivateKeyFileName;
    //             $SignprivateKeyPath = $privateKeyDir . '/' . $SignprivateKeyFileName;
    
    //             if (!file_exists($privateKeyDir)) {
    //                 mkdir($privateKeyDir, 0700, true);
    //             }
    
    //             file_put_contents($EncprivateKeyPath, $EncKeyPair['private_key']);
    //             file_put_contents($SignprivateKeyPath, $SignKeyPair['private_key']);
    //             chmod($EncprivateKeyPath, 0600);
    //             chmod($SignprivateKeyPath, 0600);
    
    //             $user = User::create([
    //                 'name' => $data['name'],
    //                 'email' => $data['email'],
    //                 'password' => Hash::make($data['password']),
    //                 'google2fa_secret' => $secret,
    //                 'public_key_enc' => $EncKeyPair['public_key'],
    //                 'public_key_sign' => $SignKeyPair['public_key']
    //             ]);
    
    //             $user->is_2fa_authenticated = true;
    //             $user->save();
    
    //             $request->session()->forget(['user_data', 'google2fa_secret']);
    //             $this->guard()->login($user);
    
    //             Album::insertAlbum('gallery', $user->id);
    
    //             return response()->json(['success' => true, 'redirect_url' => $this->redirectPath()]);
    //         } else {
    //             return response()->json(['success' => false, 'message' => 'The provided 2FA code is invalid.'], 422);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
    //     }
    // }

}