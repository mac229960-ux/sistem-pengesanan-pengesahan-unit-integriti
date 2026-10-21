<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class HeaderKeselamatan
{
    public function handle(Request $request, Closure $next)
    {
        $res = $next($request);
        $res->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $res->headers->set('X-Content-Type-Options', 'nosniff');
        $res->headers->set('Referrer-Policy', 'same-origin');
        $res->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
        if ($request->user()) {
            // Halaman kes sulit tidak boleh disimpan dalam cache pelayar.
            $res->headers->set('Cache-Control', 'no-store, private');
        }

        return $res;
    }
}
