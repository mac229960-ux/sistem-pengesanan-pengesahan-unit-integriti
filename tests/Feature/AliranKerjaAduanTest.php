<?php

namespace Tests\Feature;

use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\AiCadangan;
use App\Models\AuditLog;
use App\Models\Bukti;
use App\Models\JenisTindakan;
use App\Models\KategoriAduan;
use App\Models\OtpKod;
use App\Models\PegawaiHr;
use App\Models\RujukanPeraturan;
use App\Models\SaluranAduan;
use App\Models\User;
use App\Notifications\KodOtp;
use App\Notifications\NotifikasiAduan;
use App\Services\Ai\AiService;
use App\Services\AliranKerja;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AliranKerjaAduanTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function u(string $emel): User
    {
        return User::where('email', $emel.'@mbsp.gov.my')->firstOrFail();
    }

    public function test_log_masuk_memerlukan_otp_emel(): void
    {
        Notification::fake();
        $ketua = $this->u('ketua.integriti');

        $this->post('/log-masuk', ['email' => $ketua->email, 'password' => 'salah'])->assertSessionHasErrors('email');
        $this->assertGuest();

        $this->post('/log-masuk', ['email' => $ketua->email, 'password' => 'Integriti@2026'])->assertRedirect('/otp');
        $this->assertGuest();

        $kod = null;
        Notification::assertSentTo($ketua, KodOtp::class, function (KodOtp $n) use (&$kod) {
            $kod = (fn () => $this->kod)->call($n);

            return true;
        });

        $this->post('/otp', ['kod' => '000000'])->assertSessionHasErrors('kod');
        $this->post('/otp', ['kod' => $kod])->assertRedirect('/');
        $this->assertAuthenticatedAs($ketua);
        $this->assertNotNull(OtpKod::where('user_id', $ketua->id)->value('digunakan_pada'));
    }

    public function test_otp_boleh_dihalakan_ke_emel_tetap_uat(): void
    {
        Notification::fake();
        config(['integriti.otp.emel_tetap' => 'zulikqram@mbsp.gov.my']);
        $this->post('/log-masuk', ['email' => 'pembantu.tadbir@mbsp.gov.my', 'password' => 'Integriti@2026']);

        Notification::assertSentOnDemand(KodOtp::class, fn ($n, $ch, $notifiable) => $notifiable->routes['mail'] === 'zulikqram@mbsp.gov.my');
    }

    public function test_aliran_penuh_dari_aduan_hingga_kes_ditutup(): void
    {
        Notification::fake();
        Storage::fake('local');

        $pembantu = $this->u('pembantu.tadbir');
        $ketua = $this->u('ketua.integriti');
        $pegawai = $this->u('pegawai.siasatan1');
        $hr = PegawaiHr::first();

        // 1. Pembantu Tadbir mendaftar aduan tanpa nama
        $this->actingAs($pembantu)->post('/aduan', [
            'saluran_id' => SaluranAduan::first()->id,
            'kategori_id' => KategoriAduan::where('kod', 'RSH')->value('id'),
            'tarikh_terima' => today()->toDateString(),
            'tanpa_nama' => 1,
            'tajuk' => 'Ujian aduan suapan',
            'keterangan' => 'Pegawai '.$hr->nama.' didakwa meminta wang tunai RM500. No KP 800101-07-1234.',
            'lokasi' => 'Butterworth',
            'keutamaan' => 'tinggi',
            'pihak' => [['pegawai_hr_id' => $hr->id, 'nama' => $hr->nama, 'no_pekerja' => $hr->no_pekerja, 'peranan' => 'didakwa']],
        ])->assertRedirect();

        $aduan = Aduan::where('tajuk', 'Ujian aduan suapan')->firstOrFail();
        $this->assertSame(StatusAduan::Baru, $aduan->status);
        $this->assertMatchesRegularExpression('#^UI/\d{4}/\d{5}$#', $aduan->no_rujukan);
        $this->assertTrue($aduan->tarikh_akhir_sla->equalTo(today()->addMonths(6)));
        $this->assertNull($aduan->nama_pengadu);
        Notification::assertSentTo($ketua, NotifikasiAduan::class);

        // Pembantu Tadbir tidak boleh melihat butiran siasatan
        $this->actingAs($pembantu)->get("/aduan/{$aduan->id}?tab=bukti")->assertOk()->assertDontSee('Tambah Bukti');
        $this->actingAs($pembantu)->post("/aduan/{$aduan->id}/agih", ['pegawai_siasatan_id' => $pegawai->id])->assertForbidden();

        // 2. Ketua Unit mengagihkan kes
        $this->actingAs($ketua)->post("/aduan/{$aduan->id}/agih", ['pegawai_siasatan_id' => $pegawai->id])->assertRedirect();
        $this->assertSame(StatusAduan::Diagihkan, $aduan->fresh()->status);
        Notification::assertSentTo($pegawai, NotifikasiAduan::class, fn ($n) => $n->jenis === 'agihan');

        // Pegawai lain tidak boleh melihat kes ini
        $this->actingAs($this->u('pegawai.siasatan2'))->get("/aduan/{$aduan->id}")->assertForbidden();

        // 3. Pegawai Siasatan merekod aktiviti (status → Dalam Siasatan)
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/aktiviti", [
            'jenis' => 'temubual', 'tarikh_aktiviti' => now()->subHour()->format('Y-m-d H:i'), 'tajuk' => 'Temu bual saksi', 'butiran' => 'Saksi mengesahkan kejadian.',
        ])->assertRedirect();
        $this->assertSame(StatusAduan::DalamSiasatan, $aduan->fresh()->status);

        // 4. Muat naik bukti: disulitkan + SHA-256
        $fail = UploadedFile::fake()->createWithContent('resit.txt', 'Resit bayaran RM500');
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/bukti", ['jenis' => 'dokumen', 'tajuk' => 'Resit', 'fail' => $fail])->assertRedirect();
        $bukti = Bukti::where('aduan_id', $aduan->id)->firstOrFail();
        $this->assertSame('B001', $bukti->no_bukti);
        $this->assertSame(hash('sha256', 'Resit bayaran RM500'), $bukti->sha256);
        $this->assertStringNotContainsString('Resit bayaran', Storage::disk('local')->get($bukti->laluan_fail));
        $res = $this->actingAs($pegawai)->get("/aduan/{$aduan->id}/bukti/{$bukti->id}/muat-turun")->assertOk();
        $this->assertSame('Resit bayaran RM500', $res->getContent());
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/bukti/{$bukti->id}/sahkan", ['status_pengesahan' => 'disahkan'])->assertRedirect();
        $this->assertSame('disahkan', $bukti->fresh()->status_pengesahan);

        // 5. Cadangan AI (mod demo) — data pengadu & nama tidak dihantar
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/ai", ['soalan' => 'Perlu rujuk SPRM?'])->assertRedirect();
        $ai = AiCadangan::where('aduan_id', $aduan->id)->firstOrFail();
        $this->assertNull($ai->ralat);
        $this->assertStringContainsString('MOD DEMO', $ai->jawapan);
        $this->assertStringNotContainsString($hr->nama, $ai->prompt);
        $this->assertStringNotContainsString('800101-07-1234', $ai->prompt);
        $this->assertStringContainsString('Pegawai A', $ai->prompt);
        $this->assertNotEmpty($ai->rujukan_dipetik);
        $this->actingAs($pegawai)->post("/ai/{$ai->id}/semak", ['status_semakan' => 'diterima_sebahagian', 'ulasan_pegawai' => 'OK'])->assertRedirect();
        $this->actingAs($pegawai)->get("/aduan/{$aduan->id}?tab=ai")->assertOk()->assertSee('Ringkasan Fakta');

        // 6. Cadangan tindakan → hantar
        $jt = JenisTindakan::whereIn('kod', ['RUJUK-SPRM', 'PBT-TATATERTIB'])->pluck('id')->all();
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/cadangan", [
            'keputusan_siasatan' => 'berasas', 'dapatan' => 'Dakwaan berasas berdasarkan keterangan saksi dan resit.',
            'justifikasi' => 'Melanggar tatakelakuan.', 'jenis_tindakan' => $jt, 'ai_cadangan_id' => $ai->id,
        ])->assertRedirect();
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/cadangan/hantar")->assertRedirect();
        $this->assertSame(StatusAduan::MenungguKelulusan, $aduan->fresh()->status);

        // Pegawai tidak boleh meluluskan sendiri
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/kelulusan", ['keputusan' => 'lulus'])->assertForbidden();

        // 7. Ketua kembalikan (ulasan wajib) → pegawai pinda → hantar semula → lulus
        $this->actingAs($ketua)->post("/aduan/{$aduan->id}/kelulusan", ['keputusan' => 'kembalikan'])->assertSessionHasErrors('ulasan');
        $this->actingAs($ketua)->post("/aduan/{$aduan->id}/kelulusan", ['keputusan' => 'kembalikan', 'ulasan' => 'Tambah keterangan pegawai diadu.'])->assertRedirect();
        $this->assertSame(StatusAduan::Dikembalikan, $aduan->fresh()->status);

        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/cadangan", [
            'keputusan_siasatan' => 'berasas', 'dapatan' => 'Dakwaan berasas. Pegawai diadu gagal memberi penjelasan munasabah.',
            'justifikasi' => 'Melanggar tatakelakuan.', 'jenis_tindakan' => $jt,
        ]);
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/cadangan/hantar");
        $this->assertSame(2, $aduan->cadanganTindakan()->first()->versi);
        $this->actingAs($ketua)->get('/kelulusan')->assertOk()->assertSee($aduan->no_rujukan);
        $this->actingAs($ketua)->post("/aduan/{$aduan->id}/kelulusan", ['keputusan' => 'lulus', 'ulasan' => 'Lulus'])->assertRedirect();
        $aduan->refresh();
        $this->assertSame(StatusAduan::Diluluskan, $aduan->status);
        $this->assertSame('berasas', $aduan->keputusan_siasatan->value);

        // 8. Rekod tindakan & tutup kes
        $this->actingAs($ketua)->post("/aduan/{$aduan->id}/tutup")->assertSessionHas('ralat');
        $this->actingAs($pegawai)->post("/aduan/{$aduan->id}/tindakan", [
            'jenis_tindakan_id' => $jt[0], 'tarikh_tindakan' => today()->toDateString(), 'butiran' => 'Surat rujukan dihantar kepada SPRM.',
        ])->assertRedirect();
        $this->actingAs($ketua)->post("/aduan/{$aduan->id}/tutup")->assertRedirect();
        $aduan->refresh();
        $this->assertSame(StatusAduan::Selesai, $aduan->status);
        $this->assertTrue($aduan->selesaiDalamSla());
        $this->assertTrue($aduan->retensi_hingga->isSameDay(today()->addYears(7)));

        // 9. Jejak audit & sejarah
        $this->assertGreaterThanOrEqual(8, $aduan->sejarahStatus()->count());
        foreach (['aduan.daftar', 'status.tukar', 'bukti.tambah', 'bukti.muat_turun', 'ai.jana', 'cadangan.simpan'] as $t) {
            $this->assertTrue(AuditLog::where('aduan_id', $aduan->id)->where('tindakan', $t)->exists(), "Audit {$t} tiada");
        }

        // 10. Cetak PDF
        $this->actingAs($ketua)->get("/aduan/{$aduan->id}/cetak")->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_maklumat_pengadu_disulitkan_dalam_pangkalan_data(): void
    {
        $a = Aduan::where('tanpa_nama', false)->whereNotNull('nama_pengadu')->firstOrFail();
        $mentah = DB::table('aduan')->where('id', $a->id)->value('nama_pengadu');
        $this->assertNotSame($a->nama_pengadu, $mentah);
        $this->assertStringStartsWith('eyJ', $mentah);
    }

    public function test_peranan_tanpa_akses_kes(): void
    {
        $a = Aduan::first();
        foreach (['zulikqram', 'pengarah.jkp'] as $e) {
            $this->actingAs($this->u($e))->get('/aduan')->assertForbidden();
            $this->actingAs($this->u($e))->get("/aduan/{$a->id}")->assertForbidden();
        }
        $this->actingAs($this->u('pembantu.tadbir'))->get("/aduan/{$a->id}/cetak")->assertForbidden();
    }

    public function test_peralihan_status_tidak_sah_ditolak(): void
    {
        $a = Aduan::where('status', 'baru')->firstOrFail();
        $this->actingAs($this->u('ketua.integriti'));
        $this->expectException(\DomainException::class);
        AliranKerja::tukar($a, StatusAduan::Selesai);
    }

    public function test_peringatan_sla_dihantar_sekali_sahaja(): void
    {
        Notification::fake();
        $this->artisan('integriti:semak-sla')->assertSuccessful();
        $bil = DB::table('sla_peringatan')->count();
        $this->assertGreaterThan(0, $bil);
        $this->artisan('integriti:semak-sla')->expectsOutput('0 peringatan SLA dihantar.');
        $this->assertSame($bil, DB::table('sla_peringatan')->count());
        Notification::assertSentTo($this->u('ketua.integriti'), NotifikasiAduan::class, fn ($n) => $n->jenis === 'sla');
    }

    public function test_semua_halaman_utama_dipapar_mengikut_peranan(): void
    {
        $aduan = Aduan::whereNotNull('pegawai_siasatan_id')->where('status', 'dalam_siasatan')->firstOrFail();
        $pegawaiKes = $aduan->pegawaiSiasatan;

        $halaman = [
            'ketua.integriti' => ['/', '/aduan', '/aduan/baru', '/kelulusan', '/laporan', '/rujukan', '/notifikasi', '/profil', '/pentadbir/audit',
                "/aduan/{$aduan->id}", "/aduan/{$aduan->id}?tab=siasatan", "/aduan/{$aduan->id}?tab=bukti", "/aduan/{$aduan->id}?tab=ai",
                "/aduan/{$aduan->id}?tab=cadangan", "/aduan/{$aduan->id}?tab=sejarah", '/aduan?status=aktif&sla=lewat&susun=sla'],
            'pembantu.tadbir' => ['/', '/aduan', '/aduan/baru', "/aduan/{$aduan->id}", '/notifikasi', '/profil'],
            'pengarah.jkp' => ['/', '/laporan'],
            'zulikqram' => ['/', '/pentadbir/pengguna', '/rujukan'],
        ];
        foreach ($halaman as $e => $urls) {
            foreach ($urls as $url) {
                $this->actingAs($this->u($e))->get($url)->assertOk();
            }
        }
        foreach (['/', '/aduan', "/aduan/{$aduan->id}?tab=cadangan", "/aduan/{$aduan->id}?tab=ai"] as $url) {
            $this->actingAs($pegawaiKes)->get($url)->assertOk();
        }
        $this->actingAs($this->u('pembantu.tadbir'))->getJson('/hr/cari?q=bin')->assertOk()->assertJsonStructure([['id', 'nama', 'no_pekerja']]);
    }

    public function test_peraturan_mbsp_1995_dimuatkan_dan_digunakan_ai(): void
    {
        $this->assertFalse(RujukanPeraturan::whereIn('kod', ['PU-A-395-1993', 'PP-MBSP-KT-1995'])->exists());

        $r = RujukanPeraturan::where('kod', 'PGPU-16-1995')->firstOrFail();
        $this->assertTrue($r->teks_rasmi);
        $this->assertGreaterThanOrEqual(55, $r->seksyen()->count());
        foreach (range(1, 55) as $n) {
            $this->assertTrue($r->seksyen()->where('no_seksyen', "Peraturan {$n}")->exists(), "Peraturan {$n} tiada");
        }
        $this->assertStringContainsStringIgnoringCase('amaran', $r->seksyen()->where('no_seksyen', 'Peraturan 39')->value('kandungan'));

        // Fail Warta asal boleh dimuat turun
        $this->actingAs($this->u('pegawai.siasatan1'))->get("/rujukan/{$r->id}/fail")->assertOk()->assertHeader('content-type', 'application/pdf');

        $aduan = Aduan::whereHas('kategori', fn ($q) => $q->where('kod', 'RSH'))->whereNotNull('pegawai_siasatan_id')->firstOrFail();
        $rujukan = app(AiService::class)->cariRujukan($aduan->load('aktiviti', 'kategori'));
        $this->assertTrue($rujukan->contains(fn ($x) => str_contains($x['tajuk'], 'Seberang Perai') && str_contains($x['tajuk'], 'Peraturan 39')));
    }
}
