<?php
// app/Http/Middleware/GuestCartMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

class GuestCartMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check if guest token cookie exists
        if (!Cookie::has('guest_cart_token')) {
            // Generate new token
            $token = 'guest_' . Str::random(60);

            // Queue the cookie to be set in response
            Cookie::queue('guest_cart_token', $token, 60 * 24 * 30); // 30 days
        }

        return $next($request);
    }
}
