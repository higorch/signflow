<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthGuests
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!Auth::check()) return $next($request);

        $user = Auth::user();

        if (in_array($user->role, ['root', 'admin', 'customer', 'signer'])) {
            return redirect()->route('panel.dashboard.index');
        }

        return $next($request);
    }
}
