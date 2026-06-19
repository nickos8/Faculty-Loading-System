<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Only allow users whose status is 'active'.
     * If not active, log them out and send them back to login with a message.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // If not logged in, let other middleware (like 'auth') handle it.
        if (! $user) {
            return $next($request);
        }

        // Gate pending or declined users
        if (method_exists($user, 'isActive') && ! $user->isActive()) {
            auth()->logout();
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your account is not active yet.']);
        }

        return $next($request);
    }
}
