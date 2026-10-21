<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Audit;
use App\Services\OtpService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Log masuk dua langkah: kata laluan → OTP e-mel (BRS-009).
 */
class LogMasukController extends Controller
{
    public function __construct(private OtpService $otp) {}

    public function borang()
    {
        return view('auth.log-masuk');
    }

    public function logMasuk(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', $data['email'])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            Audit::log('auth.gagal', null, 'Log masuk gagal: '.$data['email'], userId: $user?->id);

            return back()->withInput($request->only('email'))->withErrors(['email' => 'E-mel atau kata laluan tidak sah.']);
        }

        if (! $user->aktif) {
            return back()->withInput($request->only('email'))->withErrors(['email' => 'Akaun anda tidak aktif. Sila hubungi Pentadbir Sistem.']);
        }

        $kod = $this->otp->jana($user);
        $request->session()->put('otp_user_id', $user->id);
        $request->session()->put('otp_ingat', $request->boolean('ingat'));

        if (app()->environment('local') && config('integriti.otp.papar_dev')) {
            session()->flash('otp_dev', $kod);
        }

        return redirect()->route('otp');
    }

    public function borangOtp(Request $request)
    {
        $user = $this->penggunaOtp($request);
        if (! $user) {
            return redirect()->route('login');
        }

        return view('auth.otp', ['emel' => $this->sembunyiEmel($user->email)]);
    }

    public function sahkanOtp(Request $request)
    {
        $user = $this->penggunaOtp($request);
        if (! $user) {
            return redirect()->route('login')->withErrors(['email' => 'Sesi tamat. Sila log masuk semula.']);
        }

        $request->validate(['kod' => ['required', 'digits:6']]);

        $hasil = $this->otp->sahkan($user, $request->input('kod'));
        if ($hasil !== true) {
            Audit::log('auth.otp_gagal', $user, $hasil, userId: $user->id);

            return back()->withErrors(['kod' => $hasil]);
        }

        Auth::login($user, (bool) $request->session()->pull('otp_ingat'));
        $request->session()->forget('otp_user_id');
        $request->session()->regenerate();

        $user->forceFill(['log_masuk_terakhir' => now(), 'ip_terakhir' => $request->ip()])->save();
        Audit::log('auth.log_masuk', $user, 'Log masuk berjaya');

        return redirect()->intended(route('dashboard'));
    }

    public function hantarSemula(Request $request)
    {
        $user = $this->penggunaOtp($request);
        if (! $user) {
            return redirect()->route('login');
        }

        $kod = $this->otp->jana($user);
        if (app()->environment('local') && config('integriti.otp.papar_dev')) {
            session()->flash('otp_dev', $kod);
        }

        return back()->with('info', 'Kod OTP baharu telah dihantar ke e-mel anda.');
    }

    public function logKeluar(Request $request)
    {
        Audit::log('auth.log_keluar', $request->user(), 'Log keluar');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function penggunaOtp(Request $request): ?User
    {
        $id = $request->session()->get('otp_user_id');

        return $id ? User::where('aktif', true)->find($id) : null;
    }

    private function sembunyiEmel(string $emel): string
    {
        [$nama, $domain] = explode('@', $emel) + [1 => ''];

        return mb_substr($nama, 0, 2).str_repeat('*', max(mb_strlen($nama) - 2, 3)).'@'.$domain;
    }
}
