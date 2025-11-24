<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Pastikan user sudah login
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized — Please login first.'
            ], 401);
        }

        // Cek apakah role user sesuai
        if ($user->role !== $role) {
            return response()->json([
                'message' => 'Forbidden — You do not have access to this resource.'
            ], 403);
        }

        return $next($request);
    }
}
