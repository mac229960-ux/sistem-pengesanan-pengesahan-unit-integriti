<?php

namespace App\Services;

use App\Models\OtpKod;
use App\Models\User;
use App\Notifications\KodOtp;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

class OtpService
{
    /** Jana dan hantar OTP. Pulangkan kod mentah (untuk paparan dev sahaja). */
    public function jana(User $user): string
    {
        OtpKod::where('user_id', $user->id)->whereNull('digunakan_pada')->delete();

        $kod = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $minit = config('integriti.otp.minit');

        OtpKod::create([
            'user_id' => $user->id,
            'kod_hash' => Hash::make($kod),
            'tamat_pada' => now()->addMinutes($minit),
            'ip' => request()->ip(),
        ]);

        // Fasa UAT: semua OTP boleh dihalakan ke satu e-mel tetap (INTEGRITI_OTP_EMEL_TETAP).
        $emelTetap = config('integriti.otp.emel_tetap');
        $emelTetap
            ? Notification::route('mail', $emelTetap)->notify(new KodOtp($kod, $minit, $user->name, $user->email))
            : $user->notify(new KodOtp($kod, $minit, $user->name, $user->email));

        return $kod;
    }

    /** @return true|string true jika sah, atau mesej ralat. */
    public function sahkan(User $user, string $kod): bool|string
    {
        $otp = OtpKod::where('user_id', $user->id)->whereNull('digunakan_pada')->latest('id')->first();

        if (! $otp) {
            return 'Tiada kod OTP aktif. Sila minta kod baharu.';
        }
        if ($otp->tamat_pada->isPast()) {
            return 'Kod OTP telah tamat tempoh. Sila minta kod baharu.';
        }
        if ($otp->cubaan >= config('integriti.otp.cubaan_maks')) {
            return 'Terlalu banyak cubaan. Sila minta kod baharu.';
        }

        $otp->increment('cubaan');

        if (! Hash::check($kod, $otp->kod_hash)) {
            return 'Kod OTP tidak sah.';
        }

        $otp->update(['digunakan_pada' => now()]);

        return true;
    }
}
