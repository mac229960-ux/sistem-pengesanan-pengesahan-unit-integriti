<?php

namespace Database\Seeders;

use App\Enums\Peranan;
use App\Models\User;
use Illuminate\Database\Seeder;

class PenggunaSeeder extends Seeder
{
    public const KATA_LALUAN_DEMO = 'Integriti@2026';

    public function run(): void
    {
        $pengguna = [
            ['Zulikqram (Pentadbir Sistem)', 'zulikqram@mbsp.gov.my', 'MBSP0001', 'Pegawai Teknologi Maklumat', Peranan::Pentadbir],
            ['Ketua Unit Integriti', 'ketua.integriti@mbsp.gov.my', 'MBSP0101', 'Ketua Unit Integriti', Peranan::KetuaUnit],
            ['Nurul Aina binti Hassan', 'pembantu.tadbir@mbsp.gov.my', 'MBSP0102', 'Pembantu Tadbir (P/O)', Peranan::PembantuTadbir],
            ['Ahmad Faizal bin Rahman', 'pegawai.siasatan1@mbsp.gov.my', 'MBSP0103', 'Penolong Pengarah (Siasatan)', Peranan::PegawaiSiasatan],
            ['Siti Mariam binti Osman', 'pegawai.siasatan2@mbsp.gov.my', 'MBSP0104', 'Pegawai Siasatan', Peranan::PegawaiSiasatan],
            ['Rajesh a/l Kumar', 'pegawai.siasatan3@mbsp.gov.my', 'MBSP0105', 'Pegawai Siasatan', Peranan::PegawaiSiasatan],
            ['Pengarah Khidmat Pengurusan', 'pengarah.jkp@mbsp.gov.my', 'MBSP0201', 'Pengarah Jabatan Khidmat Pengurusan', Peranan::Pengurusan],
        ];

        foreach ($pengguna as [$nama, $emel, $no, $jawatan, $peranan]) {
            User::updateOrCreate(['email' => $emel], [
                'name' => $nama, 'no_pekerja' => $no, 'jawatan' => $jawatan, 'peranan' => $peranan,
                'password' => self::KATA_LALUAN_DEMO, 'aktif' => true,
            ]);
        }
    }
}
