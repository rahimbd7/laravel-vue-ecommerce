<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VendorMiddleWare
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Unauthorized access. Please log in.');
        }
        if (!$user->isVendor()) {
            abort(403, 'Unauthorized access. Required role: vendor');
        }
        if (!$user->vendor || !$user->vendor->is_verified) {
            abort(403, 'Unauthorized access. Your vendor application is pending for verification.');
        }
        return $next($request);
    }
}
