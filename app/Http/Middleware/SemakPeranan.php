<?php

namespace App\Http\Middleware;

use App\Enums\Peranan;
use Closure;
use Illuminate\Http\Request;

/** Penggunaan: ->middleware('peranan:ketua_unit,pentadbir') */
class SemakPeranan
{
    public function handle(Request $request, Closure $next, string ...$peranan)
    {
        $user = $request->user();
        $dibenarkan = array_map(fn ($p) => Peranan::from($p), $peranan);

        abort_unless($user && $user->ada(...$dibenarkan), 403, 'Anda tidak mempunyai kebenaran untuk fungsi ini.');

        return $next($request);
    }
}
