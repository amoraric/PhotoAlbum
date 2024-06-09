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

        if (Auth::check() && $user->google2fa_secret && !$user->is_2fa_authenticated && $request->path() !== '2fa/verify' && $request->path() !== 'logout') {
            return redirect()->route('2fa.verify');
        }

        return $next($request);
    }
}
