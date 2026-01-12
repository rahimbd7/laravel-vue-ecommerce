<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {

        $user = $request->user();
        //not user
        if (!$user) {
          abort (403, 'Unauthenticated access.');
        }
        if(!in_array($user->role, $roles)) {
            abort(403, 'Unauthorized access. Required role: ' . implode(', ', $roles));
        }
        return $next($request);
    }
}
