<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureActiveAccount
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user && ! $user->isActive()) {
            // Revocation applies to existing sessions and API tokens as well as new logins.
            $user->tokens()->delete();
            if (Auth::guard('web')->check()) {
                Auth::guard('web')->logout();
            }
            Auth::guard()->forgetUser();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            abort(403, 'Korisnički račun nije aktivan.');
        }

        return $next($request);
    }
}
