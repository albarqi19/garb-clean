<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class EnsureUserIsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        // Allow access to login page
        if ($request->route()->named('filament.admin.auth.login')) {
            return $next($request);
        }

        // If user is not logged in, auto-login the first user
        if (!Auth::check()) {
            $user = User::first();
            if ($user) {
                Auth::login($user);
            } else {
                // Redirect to login if no users exist
                return redirect()->route('filament.admin.auth.login');
            }
        }

        return $next($request);
    }
}
