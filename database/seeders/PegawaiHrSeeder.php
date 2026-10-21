<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\PegawaiHr;
use Illuminate\Database\Seeder;

/** Data MOCK Sistem HR Bersepadu MBSP (nama rekaan). */
class PegawaiHrSeeder extends Seeder
{
    public function run(): void
    {
        $namaDepan = ['Mohd Azlan', 'Nor Azizah', 'Kamarul Ariffin', 'Lim Wei Ming', 'Saravanan', 'Faridah', 'Hafiz', 'Tan Mei Ling',
            'Zainal Abidin', 'Rosnah', 'Muthu', 'Shahrul Nizam', 'Aminah', 'Khairul Anuar', 'Goh Chee Keong', 'Noraini',
            'Ismail', 'Devi', 'Azman', 'Suhaila', 'Ong Boon Hock', 'Ridzuan', 'Hasnah', 'Kumaresan', 'Zulkifli',
            'Haslinda', 'Chong Kah Wai', 'Fauzi', 'Mazlina', 'Rahim', 'Yusof', 'Jamilah', 'Lee Siew Peng', 'Badrul Hisham',
            'Kalaivani', 'Shamsul', 'Rohani', 'Wong Kar Fai', 'Nasir', 'Salmah'];
        $jawatan = [
            ['Penolong Pegawai Penguatkuasa', 'KP19'], ['Pembantu Penguatkuasa', 'KP11'], ['Penolong Jurutera', 'JA29'],
            ['Jurutera', 'J41'], ['Pembantu Tadbir (Kewangan)', 'W19'], ['Penolong Pegawai Kesihatan Persekitaran', 'U29'],
            ['Pemandu Kenderaan', 'H11'], ['Pembantu Awam', 'H11'], ['Penolong Pegawai Tadbir', 'N29'], ['Pegawai Tadbir', 'N41'],
            ['Penolong Pegawai Penilaian', 'W29'], ['Juruteknik', 'JA19'], ['Pembantu Operasi', 'N11'], ['Akauntan', 'WA41'],
        ];
        $jabatan = Jabatan::pluck('id')->all();

        mt_srand(2026);
        foreach ($namaDepan as $i => $nama) {
            [$jaw, $gred] = $jawatan[$i % count($jawatan)];
            $lelaki = ! in_array($nama, ['Nor Azizah', 'Faridah', 'Tan Mei Ling', 'Rosnah', 'Aminah', 'Noraini', 'Devi', 'Suhaila', 'Hasnah', 'Haslinda', 'Mazlina', 'Jamilah', 'Lee Siew Peng', 'Kalaivani', 'Rohani', 'Salmah']);
            $penuh = match (true) {
                str_contains($nama, ' ') && preg_match('/^(Lim|Tan|Goh|Ong|Chong|Lee|Wong)/', $nama) => $nama,
                in_array($nama, ['Saravanan', 'Muthu', 'Kumaresan']) => $nama.' a/l '.['Raman', 'Subramaniam', 'Gopal'][$i % 3],
                in_array($nama, ['Devi', 'Kalaivani']) => $nama.' a/p '.['Krishnan', 'Muniandy'][$i % 2],
                default => $nama.($lelaki ? ' bin ' : ' binti ').['Abdullah', 'Ahmad', 'Ismail', 'Hamid', 'Yusof', 'Othman'][$i % 6],
            };
            $no = 'MBSP'.str_pad((string) (1000 + $i), 4, '0', STR_PAD_LEFT);

            PegawaiHr::updateOrCreate(['no_pekerja' => $no], [
                'nama' => $penuh,
                'no_kp' => sprintf('%02d%02d%02d-07-%04d', mt_rand(70, 99), mt_rand(1, 12), mt_rand(1, 28), mt_rand(1000, 9999)),
                'jawatan' => $jaw,
                'gred' => $gred,
                'jabatan_id' => $jabatan[$i % count($jabatan)],
                'emel' => strtolower(preg_replace('/[^a-z]/i', '', explode(' ', $nama)[0])).$i.'@mbsp.gov.my',
                'telefon' => '04-'.mt_rand(5000000, 5999999),
                'tarikh_lantikan' => now()->subYears(mt_rand(2, 25))->subDays(mt_rand(0, 300))->toDateString(),
                'status_perkhidmatan' => 'aktif',
                'disegerak_pada' => now(),
            ]);
        }
    }
}
