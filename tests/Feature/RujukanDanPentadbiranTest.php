<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\JenisTindakan;
use App\Models\RujukanPeraturan;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/** Menggunakan data seeder penuh pada pangkalan data ujian. */
class RujukanDanPentadbiranTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private function pengguna(string $emel): User
    {
        return User::where('email', $emel)->firstOrFail();
    }

    public function test_halaman_dipapar_ikut_peranan(): void
    {
        $admin = $this->pengguna('zulikqram@mbsp.gov.my');
        $ketua = $this->pengguna('ketua.integriti@mbsp.gov.my');
        $pegawai = $this->pengguna('pegawai.siasatan1@mbsp.gov.my');
        $r = RujukanPeraturan::first();

        foreach ([$admin, $ketua, $pegawai] as $u) {
            $this->actingAs($u)->get('/rujukan')->assertOk()->assertSee('Pustaka Peraturan');
            $this->actingAs($u)->get('/rujukan?cari=hadiah&jenis=peraturan')->assertOk();
            $this->actingAs($u)->get("/rujukan/{$r->id}")->assertOk()->assertSee($r->tajuk);
        }
        $this->actingAs($pegawai)->get('/rujukan/baru')->assertForbidden();
        $this->actingAs($ketua)->get('/rujukan/baru')->assertOk();
        $this->actingAs($ketua)->get("/rujukan/{$r->id}/kemaskini")->assertOk();

        foreach (['/pentadbir/pengguna', '/pentadbir/pengguna/create', "/pentadbir/pengguna/{$ketua->id}/edit",
            '/pentadbir/data/kategori', '/pentadbir/data/saluran', '/pentadbir/data/jabatan', '/pentadbir/data/tindakan',
            '/pentadbir/pegawai-hr', '/pentadbir/pegawai-hr?cari=bin', '/pentadbir/audit', '/pentadbir/audit?tindakan=auth&dari=2026-01-01'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
        $this->actingAs($admin)->get('/pentadbir/data/tiada')->assertNotFound();
        $this->actingAs($ketua)->get('/pentadbir/audit')->assertOk();
        $this->actingAs($ketua)->get('/pentadbir/pengguna')->assertForbidden();
        $this->actingAs($pegawai)->get('/pentadbir/audit')->assertForbidden();
    }

    public function test_muat_naik_pdf_dan_pecahan_seksyen(): void
    {
        Storage::fake('local');
        $ketua = $this->pengguna('ketua.integriti@mbsp.gov.my');

        $html = '<h1>Pekeliling Ujian</h1><p>Mukadimah pekeliling ini menerangkan tujuan dan skop pemakaian kepada semua pegawai.</p>';
        foreach ([1 => 'Tujuan', 2 => 'Larangan menerima hadiah', 3 => 'Tindakan tatatertib'] as $n => $t) {
            $html .= "<p>Perenggan {$n}. {$t}</p><p>".str_repeat("Kandungan {$t} yang perlu dipatuhi oleh pegawai. ", 5).'</p>';
        }
        $pdf = Pdf::loadHTML($html)->output();
        $fail = UploadedFile::fake()->createWithContent('pekeliling-ujian.pdf', $pdf);

        $res = $this->actingAs($ketua)->post('/rujukan', [
            'kod' => 'UJI-PDF-01', 'tajuk' => 'Pekeliling Ujian', 'jenis' => 'pekeliling', 'aktif' => '1', 'fail' => $fail,
        ]);
        $r = RujukanPeraturan::where('kod', 'UJI-PDF-01')->firstOrFail();
        $res->assertRedirect(route('rujukan.show', $r));
        Storage::disk('local')->assertExists($r->laluan_fail);

        $seksyen = $r->seksyen()->get();
        $this->assertGreaterThanOrEqual(3, $seksyen->count());
        $this->assertTrue($seksyen->contains(fn ($s) => $s->no_seksyen === 'Perenggan 2' && str_contains($s->tajuk, 'hadiah')));
        $this->assertTrue(AuditLog::where('tindakan', 'rujukan.cipta')->where('model_id', $r->id)->exists());

        // Muat naik semula dengan gantikan seksyen
        $txt = UploadedFile::fake()->createWithContent('baru.txt', "Perenggan pertama sahaja.\n\nPerenggan kedua.");
        $this->actingAs($ketua)->put("/rujukan/{$r->id}", [
            'kod' => 'UJI-PDF-01', 'tajuk' => 'Pekeliling Ujian', 'jenis' => 'pekeliling', 'aktif' => '1',
            'fail' => $txt, 'gantikan_seksyen' => '1',
        ])->assertRedirect();
        $this->assertSame(1, $r->seksyen()->count());

        // Seksyen manual
        $this->actingAs($ketua)->post("/rujukan/{$r->id}/seksyen", ['no_seksyen' => 'X1', 'kandungan' => 'Kandungan manual'])->assertRedirect();
        $s = $r->seksyen()->where('no_seksyen', 'X1')->firstOrFail();
        $this->actingAs($ketua)->put("/rujukan/{$r->id}/seksyen/{$s->id}", ['no_seksyen' => 'X2', 'kandungan' => 'Diubah'])->assertRedirect();
        $this->assertSame('X2', $s->fresh()->no_seksyen);
        $this->actingAs($ketua)->delete("/rujukan/{$r->id}/seksyen/{$s->id}")->assertRedirect();

        $laluan = $r->fresh()->laluan_fail;
        $this->actingAs($ketua)->delete("/rujukan/{$r->id}")->assertRedirect(route('rujukan.index'));
        $this->assertNull(RujukanPeraturan::find($r->id));
        Storage::disk('local')->assertMissing($laluan);
    }

    public function test_pengurusan_pengguna(): void
    {
        $admin = $this->pengguna('zulikqram@mbsp.gov.my');

        $this->actingAs($admin)->post('/pentadbir/pengguna', [
            'name' => 'Pengguna Ujian', 'email' => 'ujian.x@mbsp.gov.my', 'peranan' => 'pegawai_siasatan', 'aktif' => '1',
            'password' => 'lemah', 'password_confirmation' => 'lemah',
        ])->assertSessionHasErrors('password');

        $this->actingAs($admin)->post('/pentadbir/pengguna', [
            'name' => 'Pengguna Ujian', 'email' => 'ujian.x@mbsp.gov.my', 'peranan' => 'pegawai_siasatan', 'aktif' => '1',
            'password' => 'Kuat#Kata1aluan', 'password_confirmation' => 'Kuat#Kata1aluan',
        ])->assertRedirect(route('admin.pengguna.index'));
        $u = User::where('email', 'ujian.x@mbsp.gov.my')->firstOrFail();

        $res = $this->actingAs($admin)->post("/pentadbir/pengguna/{$u->id}/reset-kata-laluan");
        $res->assertSessionHas('kata_laluan_sementara');
        $this->assertTrue(\Hash::check(session('kata_laluan_sementara')['kata_laluan'], $u->fresh()->password));

        // Tidak boleh nyahaktif / buang peranan sendiri
        $asas = ['name' => $admin->name, 'email' => $admin->email, 'peranan' => 'pentadbir'];
        $this->actingAs($admin)->put("/pentadbir/pengguna/{$admin->id}", $asas)->assertSessionHas('ralat');
        $this->actingAs($admin)->put("/pentadbir/pengguna/{$admin->id}", ['peranan' => 'ketua_unit', 'aktif' => '1'] + $asas)->assertSessionHas('ralat');
        $this->assertTrue($admin->fresh()->aktif);
    }

    public function test_data_rujukan(): void
    {
        $admin = $this->pengguna('zulikqram@mbsp.gov.my');
        $this->actingAs($admin)->post('/pentadbir/data/tindakan', [
            'kod' => 'uji-tdk', 'nama' => 'Tindakan Ujian', 'kumpulan' => 'pentadbiran', 'susunan' => 99, 'aktif' => '1',
        ])->assertRedirect();
        $t = JenisTindakan::where('kod', 'UJI-TDK')->firstOrFail();
        $this->actingAs($admin)->post('/pentadbir/data/tindakan', ['kod' => 'UJI-TDK', 'nama' => 'X', 'kumpulan' => 'tiada'])->assertSessionHasErrors('kod');
        $this->actingAs($admin)->put("/pentadbir/data/tindakan/{$t->id}", ['kod' => 'UJI-TDK', 'nama' => 'Diubah', 'kumpulan' => 'tiada'])->assertRedirect();
        $this->assertFalse($t->fresh()->aktif);
        $this->assertSame('Diubah', $t->fresh()->nama);
        $this->actingAs($admin)->post('/pentadbir/data/kategori', ['kod' => 'UJK', 'nama' => 'Kategori Ujian', 'keterangan' => 'x', 'aktif' => '1'])->assertRedirect();
        $this->assertDatabaseHas('kategori_aduan', ['kod' => 'UJK']);
    }
}
