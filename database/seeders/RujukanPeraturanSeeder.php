<?php

namespace Database\Seeders;

use App\Models\RujukanPeraturan;
use Illuminate\Database\Seeder;

/**
 * Pustaka rujukan PERMULAAN untuk enjin cadangan AI.
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
                'kod' => 'PP-MBSP-KT-1995',
                'tajuk' => 'Peraturan-Peraturan Pegawai Awam Majlis Perbandaran Seberang Perai (Kelakuan dan Tatatertib) 1995',
                'jenis' => 'peraturan', 'penerbit' => 'Majlis Bandaraya Seberang Perai', 'tahun' => 1995,
                'sumber_url' => 'https://www.mbsp.gov.my',
                'ringkasan' => 'Peraturan kelakuan dan tatatertib yang terpakai kepada pegawai MBSP. Ringkasan topikal ini perlu digantikan dengan teks rasmi.',
                'seksyen' => [
                    ['Tatakelakuan', 'Tatakelakuan am pegawai', "Seseorang pegawai hendaklah pada setiap masa memberikan taat setia kepada Yang di-Pertuan Agong, negara dan Majlis. Pegawai tidak boleh:\n(a) membelakangkan tugas awamnya demi kepentingan peribadi;\n(b) berkelakuan yang boleh menyebabkan kepentingan peribadinya bercanggah dengan tugas awamnya;\n(c) berkelakuan yang boleh menimbulkan syak munasabah bahawa dia membiarkan kepentingan peribadi bercanggah dengan tugas awam atau menggunakan kedudukan awamnya untuk faedah sendiri;\n(d) berkelakuan yang boleh memburukkan nama atau mencemarkan nama perkhidmatan;\n(e) kurang cekap atau kurang berusaha;\n(f) tidak jujur atau tidak amanah;\n(g) tidak bertanggungjawab;\n(h) membawa atau cuba membawa pengaruh atau tekanan luar untuk menyokong atau memajukan tuntutan berhubung perkhidmatan;\n(i) ingkar perintah atau berkelakuan yang boleh ditafsirkan sebagai ingkar perintah;\n(j) cuai dalam melaksanakan tugas.\nPelanggaran mana-mana tatakelakuan ini boleh menyebabkan pegawai dikenakan tindakan tatatertib."],
                    ['Gangguan seksual', 'Larangan gangguan seksual', 'Pegawai tidak boleh melakukan gangguan seksual terhadap orang lain, sama ada secara lisan, bukan lisan, visual, isyarat atau fizikal, yang bersifat seksual dan tidak diingini oleh penerima, termasuk yang boleh menjejaskan persekitaran kerja.'],
                    ['Kerja luar', 'Larangan kerja luar tanpa kebenaran', 'Pegawai tidak boleh menjalankan apa-apa kerja luar atau perniagaan sendiri kecuali dengan kebenaran bertulis Ketua Jabatan dan tertakluk kepada syarat bahawa kerja itu tidak menjejaskan tugas rasmi, tidak menggunakan masa atau kemudahan Majlis, dan tidak menimbulkan konflik kepentingan.'],
                    ['Hadiah', 'Hadiah dan keraian', 'Pegawai tidak boleh menerima atau memberi, atau membenarkan isteri/suami atau mana-mana orang lain menerima atau memberi bagi pihaknya, apa-apa hadiah sama ada ketara atau tidak ketara yang mempunyai kaitan dengan tugas rasminya. Hadiah yang tidak dapat dielak hendaklah dilaporkan kepada Ketua Jabatan untuk keputusan. Pegawai juga tidak boleh menerima keraian yang boleh mempengaruhi pelaksanaan tugasnya.'],
                    ['Harta', 'Pengisytiharan dan pemunyaan harta', 'Pegawai hendaklah mengisytiharkan harta yang dimiliki olehnya, isteri/suami atau anak, mengikut peraturan dan pekeliling semasa. Pemilikan harta yang melebihi pendapatan sah atau tidak dapat dijelaskan boleh menjadi asas tindakan tatatertib.'],
                    ['Kewangan', 'Hidup melebihi emolumen dan keterhutangan kewangan yang serius', 'Pegawai tidak boleh hidup melebihi emolumen dan pendapatan persendiriannya yang sah. Pegawai tidak boleh berada dalam keadaan keterhutangan kewangan yang serius. Pegawai tidak boleh meminjamkan wang dengan faedah atau bertindak sebagai penjamin kepada orang yang berurusan rasmi dengannya.'],
                    ['Maklumat rasmi', 'Pernyataan awam dan penggunaan maklumat rasmi', 'Pegawai tidak boleh membuat pernyataan awam yang boleh memudaratkan dasar atau nama baik Majlis, dan tidak boleh menggunakan atau mendedahkan maklumat rasmi untuk kepentingan diri atau pihak lain tanpa kebenaran.'],
                    ['Kehadiran', 'Tidak hadir bertugas', 'Pegawai yang tidak hadir bertugas tanpa cuti atau tanpa terlebih dahulu mendapat kebenaran atau tanpa sebab yang munasabah boleh dikenakan tindakan tatatertib. Emolumen bagi tempoh tidak hadir boleh dilucut hak. Pegawai yang tidak hadir bertugas berturut-turut dan tidak dapat dikesan boleh dikenakan tindakan buang kerja mengikut prosedur yang ditetapkan.'],
                    ['Dadah', 'Penyalahgunaan dadah', 'Pegawai yang didapati menyalahgunakan dadah berbahaya boleh dikenakan tindakan tatatertib selain tindakan undang-undang lain yang terpakai.'],
                    ['Prosedur', 'Prosedur tatatertib — prinsip keadilan asasi', 'Tiada hukuman boleh dikenakan tanpa pegawai diberi peluang membela diri. Pegawai hendaklah dimaklumkan secara bertulis tentang pertuduhan atau perlakuan yang dikatakan dan diberi tempoh yang munasabah untuk mengemukakan representasi bertulis. Bagi kes yang membawa kepada buang kerja atau turun pangkat, prosedur yang lebih ketat terpakai termasuk peluang untuk didengar. Keputusan dibuat oleh Pihak Berkuasa Tatatertib yang berkenaan, bukan oleh Unit Integriti.'],
                    ['Jenis hukuman', 'Jenis-jenis hukuman tatatertib', "Pihak Berkuasa Tatatertib boleh mengenakan mana-mana satu atau gabungan dua atau lebih hukuman berikut, bergantung kepada keseriusan salah laku:\n(a) amaran;\n(b) denda;\n(c) lucut hak emolumen;\n(d) tangguh pergerakan gaji;\n(e) turun gaji;\n(f) turun pangkat;\n(g) buang kerja.\nHukuman hendaklah setimpal dengan kesalahan dan mengambil kira rekod perkhidmatan pegawai."],
                    ['Tahan kerja', 'Tahan kerja dan gantung kerja', 'Pegawai yang menghadapi prosiding jenayah atau prosiding tatatertib bagi salah laku berat boleh ditahan kerja atau digantung kerja bagi tempoh tertentu supaya siasatan tidak terganggu, tertakluk kepada syarat emolumen yang ditetapkan.'],
                ],
            ],
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
