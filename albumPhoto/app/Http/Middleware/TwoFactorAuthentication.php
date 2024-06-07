<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class TwoFactorAuthentication
{
    public function handle($request, Closure $next)
    {
        if (!Auth::check()) {
            return redirect('/'); // Redirect to welcome page if not authenticated
        }

        $user = Auth::user();

        if ($user->google2fa_secret && !$request->session()->get('2fa_authenticated', false)) {
            if (!$request->is('2fa/verify') && !$request->is('logout')) {
                return redirect()->route('2fa.verify');
            }
        }
        
        return $next($request);
    }
}
