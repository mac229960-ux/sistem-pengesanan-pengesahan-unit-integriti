<?php

namespace App\Http\Controllers;

use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;

class ProfilController extends Controller
{
    public function edit(Request $request)
    {
        return view('profil.edit', ['user' => $request->user()]);
    }

    public function kataLaluan(Request $request)
    {
        $request->validate([
            'kata_laluan_semasa' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:kata_laluan_semasa', Password::min(12)->mixedCase()->numbers()->symbols()],
        ], [], [
            'kata_laluan_semasa' => 'kata laluan semasa',
            'password' => 'kata laluan baharu',
        ]);

        $user = $request->user();
        $user->update(['password' => $request->input('password')]);
        Audit::log('profil.kata_laluan', $user, 'Kata laluan ditukar oleh pengguna');

        return back()->with('berjaya', 'Kata laluan berjaya dikemas kini.');
    }
}
