<?php

namespace App\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Allows access only to users with the 'admin' OR 'superadmin' Spatie role.
 * The original only checked for 'admin', which would block superadmins.
 */
class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || (! $user->hasRole('admin') && ! $user->hasRole('superadmin'))) {
            return response()->json([
                'success' => false,
                'message' => 'Admin access required.',
                'data'    => null,
            ], 403);
        }

        return $next($request);
    }
}
