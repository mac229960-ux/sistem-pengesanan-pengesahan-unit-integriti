<?php

namespace Database\Seeders;

use App\Models\Jabatan;
use App\Models\JenisTindakan;
use App\Models\KategoriAduan;
use App\Models\SaluranAduan;
use Illuminate\Database\Seeder;

/** Senarai rujukan (MOCK) — boleh dikemas kini oleh Pentadbir melalui skrin Pentadbiran. */
class DataRujukanSeeder extends Seeder
{
    public function run(): void
    {
        $jabatan = [
            'JKP' => 'Jabatan Khidmat Pengurusan', 'JKW' => 'Jabatan Kewangan', 'JKJ' => 'Jabatan Kejuruteraan',
            'JPP' => 'Jabatan Perancangan Pembangunan', 'JBG' => 'Jabatan Bangunan', 'JPH' => 'Jabatan Penilaian dan Pengurusan Harta',
            'JKA' => 'Jabatan Kesihatan Awam', 'JPL' => 'Jabatan Perlesenan', 'JPK' => 'Jabatan Penguatkuasaan',
            'JLS' => 'Jabatan Landskap', 'JPB' => 'Jabatan Perkhidmatan Perbandaran', 'JUU' => 'Jabatan Undang-Undang',
            'JKK' => 'Jabatan Kawalan Bangunan', 'UAD' => 'Unit Audit Dalam', 'JTM' => 'Jabatan Teknologi Maklumat',
        ];
        foreach ($jabatan as $kod => $nama) {
            Jabatan::updateOrCreate(['kod' => $kod], ['nama' => $nama]);
        }

        $kategori = [
            ['RSH', 'Rasuah / Suapan', 'Menerima atau meminta suapan, hadiah atau balasan berkaitan tugas rasmi.'],
            ['SGK', 'Salah Guna Kuasa', 'Menggunakan kedudukan atau kuasa jawatan untuk kepentingan diri atau pihak lain.'],
            ['PCA', 'Penyelewengan / Pecah Amanah', 'Penyelewengan wang, harta atau aset Majlis.'],
            ['KKP', 'Konflik Kepentingan', 'Kepentingan peribadi bercanggah dengan tugas awam.'],
            ['TTK', 'Pelanggaran Tatakelakuan', 'Tingkah laku tidak bertanggungjawab, tidak jujur atau mencemarkan nama perkhidmatan.'],
            ['THB', 'Tidak Hadir Bertugas / Ponteng', 'Tidak hadir bertugas tanpa cuti atau kebenaran, penyalahgunaan rekod kehadiran.'],
            ['GSK', 'Gangguan Seksual', 'Gangguan seksual terhadap rakan sekerja atau orang awam.'],
            ['ASM', 'Penyalahgunaan Aset Majlis', 'Penggunaan kenderaan, peralatan atau kemudahan Majlis untuk kepentingan peribadi.'],
            ['KCT', 'Kecuaian Tugas', 'Cuai atau lalai dalam melaksanakan tugas sehingga menjejaskan perkhidmatan.'],
            ['PKD', 'Pemalsuan Dokumen / Tuntutan Palsu', 'Memalsukan dokumen, tuntutan perjalanan, lebih masa atau perolehan.'],
            ['LLN', 'Lain-lain', 'Aduan integriti yang tidak termasuk dalam kategori lain.'],
        ];
        foreach ($kategori as [$kod, $nama, $ket]) {
            KategoriAduan::updateOrCreate(['kod' => $kod], ['nama' => $nama, 'keterangan' => $ket]);
        }

        $saluran = [
            'SAM' => 'Sistem Aduan MBSP (e-Aduan)', 'EML' => 'E-mel', 'TEL' => 'Telefon / Talian Aduan',
            'SRT' => 'Surat', 'KTR' => 'Kaunter / Hadir Sendiri', 'MSO' => 'Media Sosial',
            'AGN' => 'Rujukan Agensi (SPRM / JPA / dll.)', 'DLM' => 'Rujukan Dalaman Jabatan', 'WBP' => 'Pemberi Maklumat (Whistleblower)',
        ];
        foreach ($saluran as $kod => $nama) {
            SaluranAduan::updateOrCreate(['kod' => $kod], ['nama' => $nama]);
        }

        // Jenis tindakan disusun mengikut kerangka Peraturan-Peraturan Pegawai Awam MBSP
        // (Kelakuan dan Tatatertib) 1995. Rujukan peraturan merujuk P.U. (A) 395/1993; semak
        // padanan nombor peraturan dengan teks MBSP 1995 apabila tersedia.
        $tindakan = [
            ['PBT-TATATERTIB', 'Syor kepada Pihak Berkuasa Tatatertib – mulakan prosiding tatatertib', 'tatatertib', 'Kes berasas; dikemukakan kepada Lembaga/Pihak Berkuasa Tatatertib untuk prosiding.', 'Peraturan 36, P.U. (A) 395/1993'],
            ['PBT-BUANG-TURUN', 'Syor prosiding tatatertib dengan tujuan buang kerja / turun pangkat', 'tatatertib', 'Bagi salah laku berat.', 'Peraturan 37, P.U. (A) 395/1993'],
            ['HKM-AMARAN', 'Hukuman: Amaran', 'tatatertib', 'Jenis hukuman tatatertib.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['HKM-DENDA', 'Hukuman: Denda', 'tatatertib', 'Jenis hukuman tatatertib.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['HKM-LUCUT', 'Hukuman: Lucut Hak Emolumen', 'tatatertib', 'Jenis hukuman tatatertib.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['HKM-TANGGUH', 'Hukuman: Tangguh Pergerakan Gaji', 'tatatertib', 'Jenis hukuman tatatertib.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['HKM-TURUN-GAJI', 'Hukuman: Turun Gaji', 'tatatertib', 'Jenis hukuman tatatertib.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['HKM-TURUN-PANGKAT', 'Hukuman: Turun Pangkat', 'tatatertib', 'Jenis hukuman tatatertib.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['HKM-BUANG', 'Hukuman: Buang Kerja', 'tatatertib', 'Jenis hukuman tatatertib paling berat.', 'Peraturan 38, P.U. (A) 395/1993'],
            ['TAHAN-KERJA', 'Syor tahan kerja / gantung kerja sementara siasatan', 'tatatertib', 'Bagi kes jenayah atau salah laku berat.', 'Peraturan 43–45, P.U. (A) 395/1993'],
            ['SURAT-TEGURAN', 'Surat teguran / nasihat (bukan hukuman)', 'pentadbiran', 'Tindakan pentadbiran bagi kesalahan kecil.', null],
            ['KAUNSELING', 'Sesi kaunseling / bimbingan', 'pentadbiran', null, null],
            ['PERTUKARAN', 'Syor pertukaran tugas / penempatan', 'pentadbiran', 'Mengelak konflik kepentingan atau risiko berulang.', null],
            ['RUJUK-SPRM', 'Rujuk kepada Suruhanjaya Pencegahan Rasuah Malaysia (SPRM)', 'rujukan_luar', 'Bagi unsur rasuah / salah guna kuasa jenayah.', 'Akta SPRM 2009'],
            ['RUJUK-PDRM', 'Rujuk kepada Polis Diraja Malaysia (PDRM)', 'rujukan_luar', 'Bagi unsur jenayah lain.', null],
            ['PENAMBAHBAIKAN', 'Cadangan penambahbaikan sistem / SOP / kawalan dalaman', 'penambahbaikan', null, null],
            ['TIADA-TIDAK-BERASAS', 'Tiada tindakan – aduan tidak berasas', 'tiada', null, null],
            ['TIADA-BUKTI', 'Tiada tindakan – tiada bukti / tidak dapat dikesan (fail ditutup, boleh dibuka semula)', 'tiada', 'Pengecualian BRS-012.', null],
        ];
        foreach ($tindakan as $i => [$kod, $nama, $kumpulan, $ket, $ruj]) {
            JenisTindakan::updateOrCreate(['kod' => $kod], [
                'nama' => $nama, 'kumpulan' => $kumpulan, 'keterangan' => $ket, 'rujukan_peraturan' => $ruj, 'susunan' => $i + 1,
            ]);
        }
    }
}
