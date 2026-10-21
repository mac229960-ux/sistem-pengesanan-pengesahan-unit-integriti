<?php

namespace Database\Seeders;

use App\Models\RujukanPeraturan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

/**
 * Peraturan-Peraturan Pegawai Awam Majlis Perbandaran Seberang Perai (Kelakuan dan Tatatertib) 1995
 * [Pg.P.U. 16/1995, Warta Kerajaan Negeri Pulau Pinang, 28 September 1995].
 * Teks Bahasa Melayu disalin daripada imbasan Warta (data/pgpu-16-1995.json); PDF asal dilampirkan sebagai fail sumber.
 */
class PeraturanMbsp1995Seeder extends Seeder
{
    public const KOD = 'PGPU-16-1995';

    public function run(): void
    {
        $laluan = 'rujukan/pgpu-16-1995.pdf';
        Storage::disk('local')->put($laluan, file_get_contents(__DIR__.'/data/pgpu-16-1995.pdf'));

        $r = RujukanPeraturan::updateOrCreate(['kod' => self::KOD], [
            'tajuk' => 'Peraturan-Peraturan Pegawai Awam Majlis Perbandaran Seberang Perai (Kelakuan dan Tatatertib) 1995',
            'jenis' => 'peraturan',
            'penerbit' => 'Majlis Perbandaran Seberang Perai (seksyen 17(1) Akta Kerajaan Tempatan 1976)',
            'tahun' => 1995,
            'sumber_url' => null,
            'ringkasan' => 'Pg.P.U. 16/1995, Warta Kerajaan Negeri Pulau Pinang Jld. 39 Bil. 20, 28 September 1995. '
                .'Diperbuat 19 Julai 1995, disahkan 23 Ogos 1995. Peraturan kelakuan dan tatatertib yang terpakai kepada pegawai MBSP. '
                .'Teks disalin daripada imbasan Warta; rujuk PDF asal bagi tujuan rasmi.',
            'nama_fail' => 'Pg.P.U. 16-1995 Peraturan Kelakuan dan Tatatertib MPSP.pdf',
            'laluan_fail' => $laluan,
            'teks_rasmi' => true,
            'aktif' => true,
        ]);

        $data = json_decode(file_get_contents(__DIR__.'/data/pgpu-16-1995.json'), true);

        $r->seksyen()->delete();
        foreach ($data as $i => $s) {
            $r->seksyen()->create($s + ['susunan' => $i + 1]);
        }
    }
}
