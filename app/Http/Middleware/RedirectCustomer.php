<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RedirectCustomer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (! $user || ! $user->roles()->whereIn('name', ['superadmin', 'admin', 'editor'])->exists()) {
            abort_if($request->expectsJson() || app('livewire')->isDefinitelyLivewireRequest(), 403);

            return redirect()->route('moj-racun');
        }

        return $next($request);
    }
}
