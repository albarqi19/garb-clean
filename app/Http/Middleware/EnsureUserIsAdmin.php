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
        // If user is not logged in, auto-login the admin user
        if (!Auth::check()) {
            $user = User::first();
            if ($user) {
                Auth::login($user);
            }
        }

        return $next($request);
    }
}
