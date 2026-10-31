<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Logs out a user who was deactivated while their session was still alive.
 */
class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->is_active) {
            Auth::guard()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                abort(403, 'Akun Anda telah dinonaktifkan.');
            }

            return redirect()->route('login')->with('status', 'Akun Anda telah dinonaktifkan. Hubungi admin.');
        }

        return $next($request);
    }
}
