<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            ! $user
            || $user->is_admin
            || $user->password_setup_at !== null
            || (bool) $request->session()->get('auth_via_google', false)
        ) {
            return $next($request);
        }

        return redirect()->route('membership.account.setup');
    }
}
