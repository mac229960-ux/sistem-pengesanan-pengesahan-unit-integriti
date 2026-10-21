<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/** Log keluar pengguna yang telah dinyahaktifkan semasa sesi masih aktif. */
class PenggunaAktif
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user() && ! $request->user()->aktif) {
            Auth::logout();
            $request->session()->invalidate();

            return redirect()->route('login')->withErrors(['email' => 'Akaun anda telah dinyahaktifkan.']);
        }

        return $next($request);
    }
}
