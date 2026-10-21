<?php

namespace App\Services\Ai;

use Illuminate\Support\Str;

/**
 * Mod demo: menjana jawapan berasaskan peraturan mudah tanpa model bahasa.
 * Digunakan semasa pembangunan / sebelum penyedia AI diluluskan.
 */
class MockProvider implements AiProvider
{
    public function nama(): string
    {
        return 'mock';
    }

    public function model(): ?string
    {
        return 'demo-berasaskan-peraturan';
    }

    public function jana(string $sistem, string $prompt, array $konteks = []): AiRespons
    {
        $teks = ($konteks['jenis'] ?? '') === 'analisis_laporan'
            ? $this->analisisLaporan($konteks)
            : $this->cadanganKes($konteks);

        return new AiRespons($teks, ['demo' => true], $this->model());
    }

    private function cadanganKes(array $k): string
    {
        $kes = $k['kes'] ?? [];
        $bukti = collect($kes['bukti'] ?? []);
        $disahkan = $bukti->where('status', 'Disahkan')->count();
        $rujukan = collect($k['rujukan'] ?? []);
        $tindakan = collect($k['jenis_tindakan'] ?? []);

        $penilaian = match (true) {
            $disahkan >= 2 => 'Bukti yang telah disahkan **mencukupi secara prima facie** untuk menyokong dakwaan.',
            $disahkan === 1 => 'Terdapat **satu** bukti yang disahkan. Bukti sokongan tambahan disyorkan sebelum keputusan dibuat.',
            $bukti->isNotEmpty() => 'Bukti telah dikumpul tetapi **belum disahkan**. Pengesahan bukti perlu dilengkapkan.',
            default => '**Tiada bukti** direkodkan. Kes berisiko diklasifikasikan sebagai *Tiada Bukti / Tidak Dapat Dikesan*.',
        };

        $syor = match (true) {
            $disahkan >= 2 => $tindakan->firstWhere('kod', 'PBT-TATATERTIB'),
            $disahkan === 1 => $tindakan->firstWhere('kod', 'SURAT-TEGURAN'),
            default => $tindakan->firstWhere('kod', 'TIADA-BUKTI'),
        };

        $rujukanTeks = $rujukan->take(4)->map(fn ($r) => "- [{$r['label']}] {$r['tajuk']}")->implode("\n");

        return <<<MD
> ⚠️ **MOD DEMO** — Jawapan ini dijana oleh enjin peraturan mudah, bukan model AI sebenar. Tetapkan `AI_PROVIDER` kepada `claude` atau `ollama` untuk analisis sebenar.

## 1. Ringkasan Fakta
Aduan kategori **{$this->e($kes['kategori'] ?? '-')}** berhubung *"{$this->e(Str::limit($kes['tajuk'] ?? '-', 120))}"* di lokasi {$this->e($kes['lokasi'] ?? '-')}. Sebanyak {$bukti->count()} bukti dan {$this->kira($kes['aktiviti'] ?? [])} aktiviti siasatan telah direkodkan.

## 2. Isu Utama
- Sama ada pegawai terlibat telah melanggar tatakelakuan yang ditetapkan.
- Sama ada terdapat bukti yang mencukupi dan boleh disahkan.

## 3. Peruntukan Berkaitan
{$rujukanTeks}

## 4. Penilaian Bukti
{$penilaian}

## 5. Cadangan Tindakan
- **{$this->e($syor['nama'] ?? 'Rujuk Ketua Unit Integriti')}**
- Pegawai Siasatan hendaklah menilai sendiri kesesuaian cadangan ini berdasarkan fakta penuh kes.

## 6. Langkah Siasatan Seterusnya
- Lengkapkan pengesahan semua bukti yang belum disahkan.
- Rekod percakapan pegawai yang diadu dan saksi (jika belum).
- Dapatkan rekod rasmi jabatan berkaitan (log kehadiran, fail, transaksi).
MD;
    }

    private function analisisLaporan(array $k): string
    {
        $s = $k['statistik'] ?? [];
        $kategori = collect($s['ikut_kategori'] ?? [])->sortDesc();
        $teratas = $kategori->keys()->first() ?? '-';

        return <<<MD
> ⚠️ **MOD DEMO** — Analisis ini dijana oleh enjin peraturan mudah.

## Rumusan
- Jumlah aduan dalam tempoh: **{$this->e((string) ($s['jumlah'] ?? 0))}**; aktif: **{$this->e((string) ($s['aktif'] ?? 0))}**; lewat SLA: **{$this->e((string) ($s['lewat'] ?? 0))}**.
- Kategori paling kerap: **{$this->e($teratas)}**.
- Pematuhan SLA 6 bulan: **{$this->e((string) ($s['peratus_dalam_sla'] ?? '-'))}%**.

## Pemerhatian
- Kes yang lewat SLA memerlukan perhatian segera Ketua Unit.
- Pertimbangkan program kesedaran integriti bagi jabatan dengan aduan tertinggi.

## Cadangan Penambahbaikan
- Tetapkan semakan bulanan status kes bersama Pegawai Siasatan.
- Kenal pasti punca berulang bagi kategori teratas dan cadangkan penambahbaikan SOP.
MD;
    }

    private function kira($v): int
    {
        return is_countable($v) ? count($v) : 0;
    }

    private function e(?string $s): string
    {
        return str_replace(['<', '>'], ['‹', '›'], (string) $s);
    }
}
