<?php

namespace Database\Seeders;

use App\Models\RujukanPeraturan;
use Illuminate\Database\Seeder;

/**
 * Teks penuh Peraturan-Peraturan Pegawai Awam (Kelakuan dan Tatatertib) 1993 [P.U. (A) 395/1993],
 * versi dalam talian yang dikemas kini hingga pindaan P.U. (A) 252/2015, dipecahkan mengikut peraturan.
 * Sumber: data/pua-395-1993.json (disalin daripada Wikisumber yang menerbitkan semula versi dalam talian AGC).
 */
class PeraturanPegawaiAwam1993Seeder extends Seeder
{
    public function run(): void
    {
        $r = RujukanPeraturan::updateOrCreate(['kod' => 'PU-A-395-1993'], [
            'tajuk' => 'Peraturan-Peraturan Pegawai Awam (Kelakuan dan Tatatertib) 1993',
            'jenis' => 'peraturan',
            'penerbit' => 'Kerajaan Malaysia (Perkara 132(2) Perlembagaan Persekutuan)',
            'tahun' => 1993,
            'sumber_url' => 'https://ms.wikisource.org/wiki/Peraturan-Peraturan_Pegawai_Awam_(Kelakuan_dan_Tatatertib)_1993',
            'ringkasan' => 'P.U. (A) 395/1993, berkuat kuasa 15 Disember 1993, dipinda kali terakhir melalui P.U. (A) 252/2015 (1 Julai 2015). '
                .'Teks versi dalam talian (bukan cetakan semula sahih). Terpakai kepada anggota perkhidmatan awam Persekutuan; '
                .'bagi pegawai MBSP, peraturan MBSP 1995 adalah terpakai dan peraturan ini dijadikan rujukan perbandingan.',
            'teks_rasmi' => true,
            'aktif' => true,
        ]);

        $data = json_decode(file_get_contents(__DIR__.'/data/pua-395-1993.json'), true);

        $r->seksyen()->delete();
        foreach ($data as $i => $s) {
            $r->seksyen()->create($s + ['susunan' => $i + 1]);
        }
    }
}
