<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckRole
{
    /**
     * Handle an incoming request.
     * Usage in routes: ->middleware(['auth:sanctum','checkrole:admin'])
     */
    public function handle(Request $request, Closure $next, string $role)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }

        // Allow exact role match
        if ($user->role === $role) {
            return $next($request);
        }

        // Optional: allow admins to bypass
        if ($user->role === 'admin') return $next($request);

        return response()->json(['message' => 'Forbidden. Role '.$role.' required.'], 403);
    }
}