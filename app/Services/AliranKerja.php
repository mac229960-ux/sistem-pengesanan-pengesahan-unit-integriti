<?php

namespace App\Services;

use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\StatusSejarah;
use DomainException;

/**
 * Peraturan peralihan status aduan (BRS-005, BRS-013, BRS-021).
 */
class AliranKerja
{
    private const PERALIHAN = [
        'baru' => ['diagihkan', 'tidak_diteruskan'],
        'diagihkan' => ['dalam_siasatan', 'diagihkan', 'tidak_diteruskan'],
        'dalam_siasatan' => ['menunggu_kelulusan', 'diagihkan'],
        'dikembalikan' => ['menunggu_kelulusan', 'diagihkan'],
        'menunggu_kelulusan' => ['diluluskan', 'dikembalikan'],
        'diluluskan' => ['selesai'],
        'selesai' => [],
        'tidak_diteruskan' => [],
    ];

    public static function boleh(Aduan $aduan, StatusAduan $ke): bool
    {
        return in_array($ke->value, self::PERALIHAN[$aduan->status->value] ?? [], true);
    }

    public static function tukar(Aduan $aduan, StatusAduan $ke, ?string $catatan = null, array $tambahan = []): Aduan
    {
        $dari = $aduan->status;
        if (! self::boleh($aduan, $ke)) {
            throw new DomainException("Status tidak boleh ditukar dari '{$dari->label()}' kepada '{$ke->label()}'.");
        }

        $aduan->fill($tambahan + ['status' => $ke]);

        if (! $ke->aktif()) {
            $aduan->tarikh_selesai = now();
            $aduan->retensi_hingga = now()->addYears(config('integriti.retensi_tahun'))->toDateString();
        }

        $aduan->save();

        StatusSejarah::create([
            'aduan_id' => $aduan->id,
            'status_dari' => $dari->value,
            'status_ke' => $ke->value,
            'user_id' => auth()->id(),
            'catatan' => $catatan,
        ]);

        Audit::log('status.tukar', $aduan, $catatan, ['status' => $dari->value], ['status' => $ke->value]);

        return $aduan;
    }
}
