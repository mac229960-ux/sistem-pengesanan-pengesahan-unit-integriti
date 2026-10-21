<?php

namespace Database\Seeders;

use App\Models\RujukanPeraturan;
use Illuminate\Database\Seeder;

/**
 * Pustaka rujukan PERMULAAN untuk enjin cadangan AI.
 *
 * Peraturan MBSP 1995 (teks penuh) dimuatkan oleh PeraturanMbsp1995Seeder.
 *
 * PENTING: Kandungan di bawah ialah RINGKASAN UMUM yang disediakan untuk demonstrasi
 * (teks_rasmi = false) dan BUKAN teks undang-undang rasmi. Nombor peraturan sengaja tidak
 * dinyatakan kecuali yang diketahui umum. Unit Integriti hendaklah memuat naik teks rasmi
 * (PDF dari laman web JPA / MBSP / Jabatan Peguam Negara) melalui skrin Pustaka Peraturan.
 */
class RujukanPeraturanSeeder extends Seeder
{
    public function run(): void
    {
        foreach ($this->data() as $d) {
            $seksyen = $d['seksyen'];
            unset($d['seksyen']);
            $r = RujukanPeraturan::updateOrCreate(['kod' => $d['kod']], $d + ['teks_rasmi' => false, 'aktif' => true]);
            $r->seksyen()->delete();
            foreach ($seksyen as $i => [$no, $tajuk, $kandungan]) {
                $r->seksyen()->create(['no_seksyen' => $no, 'tajuk' => $tajuk, 'kandungan' => $kandungan, 'susunan' => $i + 1]);
            }
        }
    }

    private function data(): array
    {
        return [
            [
                'kod' => 'AKTA-SPRM-2009',
                'tajuk' => 'Akta Suruhanjaya Pencegahan Rasuah Malaysia 2009 (Akta 694)',
                'jenis' => 'akta', 'penerbit' => 'Parlimen Malaysia', 'tahun' => 2009,
                'sumber_url' => 'https://www.sprm.gov.my',
                'ringkasan' => 'Akta utama berkaitan kesalahan rasuah. Kes berunsur jenayah rasuah hendaklah dirujuk kepada SPRM.',
                'seksyen' => [
                    ['Seksyen 16 & 17', 'Kesalahan menerima atau memberi suapan', 'Adalah suatu kesalahan bagi mana-mana orang yang secara rasuah meminta, menerima atau bersetuju menerima, atau memberi, menjanjikan atau menawarkan apa-apa suapan sebagai dorongan atau upah untuk melakukan atau tidak melakukan sesuatu berkaitan urusan prinsipalnya atau tugas rasminya.'],
                    ['Seksyen 23', 'Salah guna jawatan atau kedudukan untuk suapan', 'Adalah suatu kesalahan bagi pegawai badan awam menggunakan jawatan atau kedudukannya untuk apa-apa suapan, sama ada bagi dirinya, saudaranya atau sekutunya, termasuk membuat keputusan atau mengambil tindakan berhubung perkara yang dia, saudaranya atau sekutunya mempunyai kepentingan.'],
                    ['Seksyen 25', 'Kewajipan melaporkan transaksi rasuah', 'Mana-mana orang yang diberi, dijanjikan atau ditawarkan suapan hendaklah melaporkan perkara itu kepada pegawai SPRM atau pegawai polis yang terdekat. Kegagalan melaporkan adalah suatu kesalahan.'],
                    ['Rujukan', 'Rujukan kes kepada SPRM', 'Sekiranya siasatan Unit Integriti mendapati terdapat unsur jenayah rasuah, kes hendaklah dirujuk kepada SPRM. Siasatan pentadbiran/tatatertib boleh diteruskan secara selari tertakluk kepada nasihat SPRM supaya tidak menjejaskan siasatan jenayah.'],
                ],
            ],
            [
                'kod' => 'AKTA-PPM-2010',
                'tajuk' => 'Akta Perlindungan Pemberi Maklumat 2010 (Akta 711)',
                'jenis' => 'akta', 'penerbit' => 'Parlimen Malaysia', 'tahun' => 2010,
                'ringkasan' => 'Melindungi identiti dan kepentingan pemberi maklumat yang membuat pendedahan kelakuan tidak wajar secara suci hati.',
                'seksyen' => [
                    ['Perlindungan', 'Kerahsiaan identiti pemberi maklumat', 'Maklumat yang boleh mendedahkan identiti pemberi maklumat hendaklah dirahsiakan. Pemberi maklumat dilindungi daripada tindakan memudaratkan (detrimental action) akibat pendedahan yang dibuat secara suci hati kepada agensi penguatkuasaan.'],
                    ['Pembatalan', 'Pembatalan perlindungan', 'Perlindungan boleh dibatalkan jika pendedahan dibuat secara tidak suci hati, mengandungi maklumat palsu yang diketahui, atau pemberi maklumat sendiri terlibat dalam kelakuan tidak wajar tersebut.'],
                ],
            ],
            [
                'kod' => 'AKTA-ARR-1972',
                'tajuk' => 'Akta Rahsia Rasmi 1972 (Akta 88)',
                'jenis' => 'akta', 'penerbit' => 'Parlimen Malaysia', 'tahun' => 1972,
                'ringkasan' => 'Pengendalian dokumen terperingkat. Rekod siasatan Unit Integriti lazimnya dikelaskan SULIT.',
                'seksyen' => [
                    ['Am', 'Pengendalian maklumat terperingkat', 'Dokumen dan maklumat rasmi yang dikelaskan hendaklah dikendalikan, disimpan dan dihantar mengikut tahap pengelasan. Pendedahan tanpa kebenaran adalah suatu kesalahan. Akses kepada fail siasatan hendaklah terhad kepada pegawai yang perlu mengetahui (need-to-know).'],
                ],
            ],
            [
                'kod' => 'JPA-GP-HADIAH',
                'tajuk' => 'Pekeliling / Garis Panduan JPA Berkaitan Penerimaan Hadiah dan Konflik Kepentingan',
                'jenis' => 'pekeliling', 'penerbit' => 'Jabatan Perkhidmatan Awam', 'tahun' => null,
                'sumber_url' => 'https://www.jpa.gov.my',
                'ringkasan' => 'PLACEHOLDER — Muat naik pekeliling JPA yang berkuat kuasa. Nombor dan tahun pekeliling perlu disahkan.',
                'seksyen' => [
                    ['Am', 'Prinsip penerimaan hadiah', 'Pegawai awam dilarang menerima hadiah daripada pihak yang mempunyai urusan rasmi dengannya. Hadiah yang tidak dapat ditolak hendaklah dilaporkan dan diserahkan kepada Ketua Jabatan untuk keputusan sama ada dipulangkan, disimpan sebagai harta jabatan atau dibenarkan disimpan.'],
                    ['Konflik', 'Pengisytiharan konflik kepentingan', 'Pegawai hendaklah mengisytiharkan apa-apa kepentingan peribadi yang boleh bercanggah dengan tugas rasmi, termasuk dalam urusan perolehan, perlesenan, penguatkuasaan dan kelulusan, dan menarik diri daripada proses membuat keputusan yang berkaitan.'],
                ],
            ],
            [
                'kod' => 'SOP-UI-MBSP',
                'tajuk' => 'SOP Pengurusan Aduan Unit Integriti MBSP (Draf mengikut BRS)',
                'jenis' => 'sop', 'penerbit' => 'Unit Integriti, Jabatan Khidmat Pengurusan', 'tahun' => 2026,
                'ringkasan' => 'Aliran kerja dalaman pengurusan aduan integriti berdasarkan BRS Sistem Pengesanan dan Pengesahan.',
                'seksyen' => [
                    ['Langkah 1', 'Penerimaan aduan', 'Aduan diterima daripada pelbagai saluran dan didaftarkan oleh Pembantu Tadbir. Aduan tanpa nama diterima sekiranya terdapat maklumat aduan, lokasi dan pihak terlibat yang mencukupi.'],
                    ['Langkah 2', 'Agihan dan siasatan', 'Ketua Unit Integriti mengagihkan aduan kepada Pegawai Siasatan. Pegawai Siasatan menjalankan pengesanan dan pengesahan, merekod aktiviti siasatan dan mengumpul bukti.'],
                    ['Langkah 3', 'Tempoh penyelesaian', 'Setiap aduan hendaklah diselesaikan dalam tempoh enam (6) bulan dari tarikh aduan diterima. Peringatan dikeluarkan sebelum tempoh tamat.'],
                    ['Langkah 4', 'Cadangan dan kelulusan', 'Pegawai Siasatan menyediakan rumusan dapatan dan cadangan tindakan. Cadangan AI adalah sebagai panduan sahaja. Kelulusan dibuat secara manual oleh Ketua Unit Integriti; tiada kelulusan automatik.'],
                    ['Langkah 5', 'Tindakan dan penutupan', 'Kes dianggap selesai selepas tindakan diambil dan direkodkan. Rekod disimpan sekurang-kurangnya tujuh (7) tahun.'],
                    ['Pengecualian', 'Bukti tidak dapat dikesan', 'Sekiranya bukti tidak dapat dikesan selepas usaha munasabah, kes boleh dicadangkan ditutup dengan keputusan "Tiada Bukti" tertakluk kelulusan Ketua Unit, dan boleh dibuka semula jika maklumat baharu diterima.'],
                ],
            ],
        ];
    }
}
