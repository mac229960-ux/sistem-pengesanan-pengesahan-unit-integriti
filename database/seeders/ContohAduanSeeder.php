<?php

namespace Database\Seeders;

use App\Enums\KeputusanSiasatan;
use App\Enums\Peranan;
use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\CadanganTindakan;
use App\Models\JenisTindakan;
use App\Models\KategoriAduan;
use App\Models\Kelulusan;
use App\Models\PegawaiHr;
use App\Models\SaluranAduan;
use App\Models\StatusSejarah;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/** Data contoh (rekaan) untuk demonstrasi dan UAT. */
class ContohAduanSeeder extends Seeder
{
    public function run(): void
    {
        $pembantu = User::where('peranan', Peranan::PembantuTadbir)->first();
        $ketua = User::where('peranan', Peranan::KetuaUnit)->first();
        $pegawai = User::where('peranan', Peranan::PegawaiSiasatan)->get();
        $hr = PegawaiHr::with('jabatan')->get();
        $sla = config('integriti.sla_bulan');

        $kes = [
            // [kategori, saluran, tajuk, keterangan, lokasi, hari_lalu, status, keutamaan]
            ['RSH', 'SAM', 'Dakwaan pegawai penguatkuasa meminta wang untuk tidak mengeluarkan kompaun', 'Pengadu mendakwa seorang pegawai penguatkuasa meminta wang tunai RM300 supaya kompaun premis perniagaan makanan tidak dikeluarkan. Kejadian berlaku semasa pemeriksaan premis pada waktu petang.', 'Jalan Bagan Luar, Butterworth', 250, 'selesai', 'tinggi'],
            ['THB', 'DLM', 'Pegawai kerap tidak hadir bertugas tanpa kebenaran', 'Rekod kehadiran menunjukkan pegawai tidak hadir bertugas sebanyak 12 hari dalam tempoh dua bulan tanpa permohonan cuti. Ketua bahagian telah memberi teguran lisan.', 'Depoh Perkhidmatan Perbandaran, Bukit Mertajam', 230, 'selesai', 'sederhana'],
            ['ASM', 'EML', 'Kenderaan Majlis digunakan untuk urusan peribadi pada hujung minggu', 'Gambar yang dikemukakan menunjukkan kenderaan pacuan empat roda berlogo MBSP diletakkan di pusat membeli-belah pada hari Ahad. Didakwa digunakan oleh pemandu jabatan untuk urusan keluarga.', 'Sunway Carnival Mall, Seberang Jaya', 215, 'selesai', 'sederhana'],
            ['KKP', 'WBP', 'Dakwaan konflik kepentingan dalam penilaian sebut harga', 'Pemberi maklumat mendakwa seorang penolong jurutera terlibat dalam penilaian sebut harga kerja penyelenggaraan longkang sedangkan syarikat yang berjaya dimiliki oleh adik iparnya.', 'Jabatan Kejuruteraan, Menara Bandaraya', 200, 'diluluskan', 'tinggi'],
            ['PKD', 'DLM', 'Tuntutan kerja lebih masa yang diragui', 'Audit dalaman mendapati tuntutan kerja lebih masa bagi bulan Mac dan April tidak selari dengan log keluar masuk pintu dan rekod GPS kenderaan.', 'Jabatan Landskap', 185, 'menunggu_kelulusan', 'tinggi'],
            ['SGK', 'SRT', 'Kelulusan permit iklan dipercepatkan untuk syarikat tertentu', 'Surat aduan daripada pengusaha iklan mendakwa permohonan syarikat tertentu diluluskan dalam masa dua hari sedangkan permohonan lain mengambil masa lebih sebulan.', 'Jabatan Perlesenan', 175, 'dalam_siasatan', 'sederhana'],
            ['TTK', 'MSO', 'Pegawai berkelakuan kasar terhadap orang awam di kaunter', 'Video tular di media sosial menunjukkan pegawai kaunter meninggikan suara dan menghalau warga emas yang ingin membuat bayaran cukai taksiran.', 'Kaunter Bayaran, Pejabat Cawangan Seberang Perai Selatan', 182, 'dalam_siasatan', 'sederhana'],
            ['RSH', 'AGN', 'Rujukan SPRM: dakwaan suapan berkaitan kelulusan pelan bangunan', 'SPRM merujuk maklumat bahawa terdapat dakwaan pegawai menerima hadiah berupa telefon pintar daripada perunding bagi mempercepatkan kelulusan pelan bangunan.', 'Jabatan Bangunan', 160, 'dalam_siasatan', 'kritikal'],
            ['GSK', 'KTR', 'Aduan gangguan seksual oleh penyelia', 'Pengadu (kakitangan wanita) mendakwa penyelia menghantar mesej berunsur lucah melalui aplikasi WhatsApp dan membuat komen tidak sopan di tempat kerja.', 'Jabatan Kesihatan Awam', 120, 'dalam_siasatan', 'kritikal'],
            ['KCT', 'SAM', 'Kerja penyelenggaraan lampu jalan tidak dilaksanakan walaupun direkod selesai', 'Penduduk mendakwa lampu jalan di taman perumahan rosak lebih tiga bulan walaupun sistem merekodkan kerja telah selesai.', 'Taman Desa Damai, Nibong Tebal', 100, 'dalam_siasatan', 'rendah'],
            ['PCA', 'DLM', 'Kekurangan wang kutipan di kaunter pasar', 'Penyelarasan kutipan sewa gerai mendapati kekurangan RM4,250 bagi tempoh tiga minggu. Kutipan dikendalikan oleh dua orang pegawai secara bergilir.', 'Pasar Awam Bukit Mertajam', 90, 'dikembalikan', 'tinggi'],
            ['SGK', 'TEL', 'Penguatkuasa didakwa memilih premis tertentu sahaja untuk tindakan', 'Pemanggil mendakwa pegawai penguatkuasa hanya mengambil tindakan terhadap peniaga tertentu dan membiarkan peniaga lain yang dikatakan rakan pegawai tersebut.', 'Pasar Malam Seberang Jaya', 75, 'diagihkan', 'sederhana'],
            ['ASM', 'SAM', 'Peralatan pejabat dibawa pulang tanpa kebenaran', 'Pengadu mendakwa komputer riba dan pencetak jabatan dibawa pulang oleh pegawai dan tidak dipulangkan.', 'Jabatan Penilaian dan Pengurusan Harta', 60, 'diagihkan', 'rendah'],
            ['TTK', 'EML', 'Pegawai menjalankan perniagaan dalam waktu pejabat', 'Pegawai didakwa menjual produk kesihatan secara dalam talian dan menerima pelanggan di pejabat semasa waktu bekerja.', 'Jabatan Kewangan', 40, 'baru', 'rendah'],
            ['RSH', 'WBP', 'Dakwaan "duit kopi" untuk pengeluaran lesen penjaja', 'Pemberi maklumat mendakwa terdapat individu yang mengaku mempunyai hubungan dengan pegawai perlesenan meminta bayaran tambahan untuk mempercepat lesen penjaja.', 'Jabatan Perlesenan', 25, 'baru', 'tinggi'],
            ['THB', 'DLM', 'Penyalahgunaan rekod kehadiran (punch kad bagi pihak rakan)', 'Rakaman CCTV menunjukkan seorang pegawai merekod kehadiran menggunakan kad milik rakan sekerja.', 'Jabatan Perkhidmatan Perbandaran', 12, 'baru', 'sederhana'],
            ['KKP', 'SRT', 'Pegawai menilai permohonan lesen premis milik keluarga', 'Pengadu mendakwa pegawai yang memeriksa permohonan lesen premis kecantikan ialah suami kepada pemilik premis.', 'Jabatan Perlesenan', 5, 'baru', 'sederhana'],
            ['LLN', 'SAM', 'Aduan berkaitan perkhidmatan kutipan sampah', 'Aduan mengenai kelewatan kutipan sampah tanpa unsur salah laku pegawai.', 'Taman Sri Rambai, Bukit Mertajam', 210, 'tidak_diteruskan', 'rendah'],
            ['KCT', 'TEL', 'Dakwaan kelewatan meluluskan bayaran kontraktor', 'Kontraktor mendakwa bayaran tertangguh lebih 90 hari dan mengesyaki pegawai sengaja melambatkan proses.', 'Jabatan Kewangan', 205, 'dalam_siasatan', 'sederhana'],
            ['PKD', 'AGN', 'Sijil perubatan dipercayai palsu', 'Klinik swasta mengesahkan sijil cuti sakit yang dikemukakan oleh seorang pegawai tidak dikeluarkan oleh klinik tersebut.', 'Jabatan Kawalan Bangunan', 195, 'menunggu_kelulusan', 'tinggi'],
        ];

        $aktivitiContoh = [
            ['temubual', 'Rakaman percakapan pengadu', 'Pengadu ditemui dan memberikan keterangan lanjut mengenai kronologi kejadian. Pengadu bersedia menjadi saksi.'],
            ['semakan_dokumen', 'Semakan rekod jabatan', 'Rekod kehadiran, log kenderaan dan fail berkaitan telah disemak bersama ketua bahagian. Terdapat percanggahan pada beberapa tarikh.'],
            ['lawatan', 'Lawatan ke lokasi kejadian', 'Pemeriksaan lokasi dijalankan. Gambar lokasi diambil dan saksi di sekitar ditemui.'],
            ['temubual', 'Rakaman percakapan pegawai yang diadu', 'Pegawai yang diadu memberikan penjelasan dan menafikan sebahagian dakwaan. Pegawai memaklumkan akan mengemukakan dokumen sokongan.'],
        ];

        $buktiContoh = [
            ['dokumen', 'Salinan rekod kehadiran / log', 'Cetakan sistem rekod kehadiran bagi tempoh berkaitan.'],
            ['gambar', 'Gambar di lokasi', 'Gambar yang diambil semasa lawatan / dikemukakan pengadu.'],
            ['kenyataan', 'Kenyataan bertulis saksi', 'Kenyataan bertulis saksi yang ditandatangani.'],
        ];

        mt_srand(42);
        $tahun = now()->year;
        foreach ($kes as $i => [$kat, $sal, $tajuk, $ket, $lokasi, $hari, $status, $keutamaan]) {
            $terima = Carbon::today()->subDays($hari);
            $statusEnum = StatusAduan::from($status);
            $pegawaiKes = $statusEnum === StatusAduan::Baru || $statusEnum === StatusAduan::TidakDiteruskan ? null : $pegawai[$i % $pegawai->count()];
            $didakwa = $hr[$i % $hr->count()];
            $tanpaNama = in_array($sal, ['WBP', 'MSO', 'TEL']) || $i % 4 === 0;

            $a = Aduan::create([
                'no_rujukan' => sprintf('UI/%d/%05d', $terima->year === $tahun ? $tahun : $terima->year, $i + 1),
                'saluran_id' => SaluranAduan::where('kod', $sal)->value('id'),
                'kategori_id' => KategoriAduan::where('kod', $kat)->value('id'),
                'tarikh_terima' => $terima,
                'tarikh_kejadian' => $terima->copy()->subDays(mt_rand(3, 30)),
                'tanpa_nama' => $tanpaNama,
                'nama_pengadu' => $tanpaNama ? null : ['Encik Hamdan bin Salleh', 'Puan Lee Ai Ling', 'Encik Murugan a/l Velu', 'Puan Rozita binti Kassim'][$i % 4],
                'telefon_pengadu' => $tanpaNama ? null : '012-'.mt_rand(3000000, 9999999),
                'emel_pengadu' => $tanpaNama ? null : 'pengadu'.$i.'@contoh.my',
                'tajuk' => $tajuk,
                'keterangan' => $ket,
                'lokasi' => $lokasi,
                'jabatan_terlibat_id' => $didakwa->jabatan_id,
                'keutamaan' => $keutamaan,
                'status' => $statusEnum,
                'pegawai_siasatan_id' => $pegawaiKes?->id,
                'diagih_oleh' => $pegawaiKes ? $ketua->id : null,
                'tarikh_agihan' => $pegawaiKes ? $terima->copy()->addDays(3) : null,
                'tarikh_akhir_sla' => $terima->copy()->addMonths($sla),
                'dicipta_oleh' => $pembantu->id,
                'sebab_tidak_diteruskan' => $statusEnum === StatusAduan::TidakDiteruskan ? 'Aduan berkaitan kualiti perkhidmatan, bukan salah laku integriti. Dirujuk kepada jabatan berkaitan melalui Sistem Aduan MBSP.' : null,
            ]);
            $a->created_at = $terima->copy()->setTime(9, 30);
            $a->save();

            $a->pihakTerlibat()->create([
                'pegawai_hr_id' => $didakwa->id, 'nama' => $didakwa->nama, 'no_pekerja' => $didakwa->no_pekerja,
                'jawatan' => $didakwa->jawatan.' ('.$didakwa->gred.')', 'jabatan' => $didakwa->jabatan?->nama, 'peranan' => 'didakwa',
            ]);

            $this->sejarah($a, null, StatusAduan::Baru, $pembantu, $terima);
            if ($statusEnum === StatusAduan::TidakDiteruskan) {
                $this->selesai($a, StatusAduan::Baru, StatusAduan::TidakDiteruskan, $ketua, $terima->copy()->addDays(7));

                continue;
            }
            if (! $pegawaiKes) {
                continue;
            }
            $this->sejarah($a, StatusAduan::Baru, StatusAduan::Diagihkan, $ketua, $terima->copy()->addDays(3));
            if ($statusEnum === StatusAduan::Diagihkan) {
                continue;
            }
            $this->sejarah($a, StatusAduan::Diagihkan, StatusAduan::DalamSiasatan, $pegawaiKes, $terima->copy()->addDays(5));

            // Aktiviti & bukti
            $bilAkt = min(4, 1 + intdiv($hari, 50));
            for ($k = 0; $k < $bilAkt; $k++) {
                [$jenis, $t, $b] = $aktivitiContoh[$k];
                $a->aktiviti()->create(['user_id' => $pegawaiKes->id, 'jenis' => $jenis, 'tajuk' => $t, 'butiran' => $b,
                    'tarikh_aktiviti' => $terima->copy()->addDays(7 + $k * 12)->setTime(10, 0)]);
            }
            $bilBukti = min(3, intdiv($hari, 50));
            for ($k = 0; $k < $bilBukti; $k++) {
                [$jenis, $t, $b] = $buktiContoh[$k];
                $disahkan = $k < $bilBukti - 1 || in_array($status, ['selesai', 'diluluskan', 'menunggu_kelulusan']);
                $a->bukti()->create([
                    'no_bukti' => 'B'.str_pad((string) ($k + 1), 3, '0', STR_PAD_LEFT), 'jenis' => $jenis, 'tajuk' => $t, 'keterangan' => $b,
                    'sumber' => $k === 0 ? 'Jabatan berkaitan' : 'Pengadu / saksi', 'tarikh_diperoleh' => $terima->copy()->addDays(10 + $k * 10),
                    'status_pengesahan' => $disahkan ? 'disahkan' : 'belum_disahkan',
                    'disahkan_oleh' => $disahkan ? $pegawaiKes->id : null, 'tarikh_disahkan' => $disahkan ? $terima->copy()->addDays(20 + $k * 10) : null,
                    'dimuatnaik_oleh' => $pegawaiKes->id,
                ]);
            }

            if ($statusEnum === StatusAduan::DalamSiasatan) {
                continue;
            }

            // Cadangan tindakan
            $berasas = $i % 5 !== 1;
            $keputusan = $berasas ? KeputusanSiasatan::Berasas : KeputusanSiasatan::TiadaBukti;
            $tindakanKod = $berasas ? ($kat === 'RSH' ? ['RUJUK-SPRM', 'PBT-TATATERTIB'] : ['PBT-TATATERTIB']) : ['TIADA-BUKTI'];
            $tarikhHantar = $terima->copy()->addDays(min($hari - 10, 120));
            $c = CadanganTindakan::create([
                'aduan_id' => $a->id, 'versi' => 1, 'disediakan_oleh' => $pegawaiKes->id,
                'keputusan_siasatan' => $keputusan,
                'dapatan' => $berasas
                    ? 'Hasil siasatan mendapati dakwaan adalah berasas. Keterangan saksi dan rekod jabatan menyokong dakwaan pengadu.'
                    : 'Siasatan tidak dapat mengesan bukti yang mencukupi untuk menyokong dakwaan walaupun semua saksi telah ditemui.',
                'justifikasi' => $berasas
                    ? 'Perlakuan pegawai bertentangan dengan tatakelakuan pegawai awam (tidak jujur / tidak bertanggungjawab).'
                    : 'Tiada bukti dokumentar atau saksi yang dapat mengesahkan dakwaan.',
                'peruntukan_peraturan' => $berasas ? 'Peraturan-Peraturan Pegawai Awam MBSP (Kelakuan dan Tatatertib) 1995 – Tatakelakuan' : null,
                'status' => 'dihantar', 'dihantar_pada' => $tarikhHantar,
            ]);
            $c->jenisTindakan()->sync(JenisTindakan::whereIn('kod', $tindakanKod)->pluck('id'));
            $this->sejarah($a, StatusAduan::DalamSiasatan, StatusAduan::MenungguKelulusan, $pegawaiKes, $tarikhHantar);

            if ($statusEnum === StatusAduan::Dikembalikan) {
                $c->update(['status' => 'dikembalikan']);
                Kelulusan::create(['cadangan_tindakan_id' => $c->id, 'aduan_id' => $a->id, 'pelulus_id' => $ketua->id,
                    'keputusan' => 'kembalikan', 'ulasan' => 'Sila dapatkan rekod penyelarasan kutipan harian dan keterangan pegawai kedua sebelum cadangan dikemukakan semula.']);
                $this->sejarah($a, StatusAduan::MenungguKelulusan, StatusAduan::Dikembalikan, $ketua, $tarikhHantar->copy()->addDays(3));

                continue;
            }
            if ($statusEnum === StatusAduan::MenungguKelulusan) {
                continue;
            }

            $c->update(['status' => 'diluluskan']);
            $tarikhLulus = $tarikhHantar->copy()->addDays(4);
            Kelulusan::create(['cadangan_tindakan_id' => $c->id, 'aduan_id' => $a->id, 'pelulus_id' => $ketua->id,
                'keputusan' => 'lulus', 'ulasan' => 'Diluluskan. Teruskan tindakan seperti dicadangkan.', 'created_at' => $tarikhLulus]);
            $a->update(['keputusan_siasatan' => $keputusan]);
            $this->sejarah($a, StatusAduan::MenungguKelulusan, StatusAduan::Diluluskan, $ketua, $tarikhLulus);

            if ($statusEnum === StatusAduan::Selesai) {
                $tarikhTindakan = $tarikhLulus->copy()->addDays(10);
                foreach ($c->jenisTindakan as $jt) {
                    $a->tindakanDiambil()->create(['jenis_tindakan_id' => $jt->id, 'tarikh_tindakan' => $tarikhTindakan,
                        'no_rujukan_surat' => 'MBSP/JKP/UI/'.($i + 100), 'butiran' => 'Tindakan telah dilaksanakan dan surat dikeluarkan.', 'direkod_oleh' => $pegawaiKes->id]);
                }
                $this->selesai($a, StatusAduan::Diluluskan, StatusAduan::Selesai, $ketua, $tarikhTindakan->copy()->addDays(1));
            }
        }
    }

    private function sejarah(Aduan $a, ?StatusAduan $dari, StatusAduan $ke, User $u, Carbon $bila): void
    {
        StatusSejarah::create(['aduan_id' => $a->id, 'status_dari' => $dari?->value, 'status_ke' => $ke->value, 'user_id' => $u->id, 'created_at' => $bila]);
    }

    private function selesai(Aduan $a, StatusAduan $dari, StatusAduan $ke, User $u, Carbon $bila): void
    {
        $a->update(['status' => $ke, 'tarikh_selesai' => $bila, 'retensi_hingga' => $bila->copy()->addYears(config('integriti.retensi_tahun'))]);
        $this->sejarah($a, $dari, $ke, $u, $bila);
    }
}
