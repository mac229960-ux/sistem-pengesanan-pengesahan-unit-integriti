<?php

namespace App\Services\Hr;

use Illuminate\Support\Collection;

/**
 * Antara muka integrasi Sistem HR Bersepadu MBSP (BRS-008).
 * Pelaksanaan semasa: MockHrService. Gantikan dengan ApiHrService apabila API tersedia.
 */
interface HrService
{
    /** Cari pegawai mengikut nama / no. pekerja / no. KP. */
    public function cari(string $kata, int $had = 20): Collection;

    /** Dapatkan butiran penuh seorang pegawai. */
    public function butiran(string $noPekerja): ?array;
}
