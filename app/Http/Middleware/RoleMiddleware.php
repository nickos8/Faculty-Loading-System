<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return redirect()->route('login');
        }

        if (empty($roles)) {
            abort(403, 'No role specified.');
        }

        // Laravel passes "role:a,b" as one string param. Split it safely.
        if (count($roles) === 1) {
            $roles = preg_split('/[,\|]/', $roles[0]); // support comma or pipe
        }

        // Normalize and sanitize: trim, convert hyphens to underscores
        $roles = array_values(array_unique(array_filter(array_map(
            fn ($r) => str_replace('-', '_', trim($r)),
            $roles
        ))));

        // Ensure relation is available
        $user->loadMissing('roles');

        // Use your model helper if present; fall back to a direct query
        if (method_exists($user, 'hasAnyRole')) {
            if (!$user->hasAnyRole($roles)) {
                abort(403, 'You do not have permission to access this resource .');
            }
        } else {
            $has = $user->roles()->whereIn('name', $roles)->exists();
            if (!$has) {
                abort(403, 'You do not have permission to access this resource .');
            }
        }

        return $next($request);
    }
}
