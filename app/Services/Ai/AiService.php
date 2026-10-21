<?php

namespace App\Services\Ai;

use App\Models\Aduan;
use App\Models\AduanPihakTerlibat;
use App\Models\AiCadangan;
use App\Models\Bukti;
use App\Models\JenisTindakan;
use App\Models\RujukanSeksyen;
use App\Models\SiasatanAktiviti;
use App\Models\User;
use App\Services\Audit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Cadangan AI (BRS-019, BRS-020). AI hanya memberi pandangan; keputusan kekal pada pegawai.
 */
class AiService
{
    public function __construct(private AiProvider $provider) {}

    public static function provider(): AiProvider
    {
        return match (config('integriti.ai.provider')) {
            'claude' => new ClaudeProvider,
            'ollama' => new OllamaProvider,
            default => new MockProvider,
        };
    }

    // ------------------------------------------------------------------
    // Cadangan untuk satu kes
    // ------------------------------------------------------------------
    public function cadanganKes(Aduan $aduan, User $user, ?string $soalanTambahan = null): AiCadangan
    {
        $aduan->loadMissing(['kategori', 'saluran', 'jabatanTerlibat', 'pihakTerlibat', 'aktiviti', 'bukti']);

        $kes = $this->ringkasanKes($aduan);
        $rujukan = $this->cariRujukan($aduan);
        $jenisTindakan = JenisTindakan::where('aktif', true)->orderBy('susunan')
            ->get(['kod', 'nama', 'kumpulan', 'rujukan_peraturan'])->toArray();

        $konteks = [
            'jenis' => 'cadangan_kes',
            'kes' => $kes,
            'rujukan' => $rujukan->all(),
            'jenis_tindakan' => $jenisTindakan,
        ];

        $prompt = $this->binaPromptKes($kes, $rujukan, $jenisTindakan, $soalanTambahan);

        return $this->laksana($user, 'cadangan_kes', $this->sistemKes(), $prompt, $konteks, $aduan->id, $rujukan);
    }

    // ------------------------------------------------------------------
    // Analisis laporan (statistik agregat sahaja — tiada data peribadi)
    // ------------------------------------------------------------------
    public function analisisLaporan(array $statistik, User $user): AiCadangan
    {
        $sistem = <<<'TXT'
Anda ialah penganalisis data integriti untuk Unit Integriti, Majlis Bandaraya Seberang Perai (MBSP).
Anda menerima statistik agregat aduan integriti (tiada data peribadi). Tulis analisis ringkas dalam Bahasa Melayu
dengan bahagian: "## Rumusan", "## Trend dan Corak", "## Risiko Utama", "## Cadangan Penambahbaikan".
Gunakan hanya angka yang diberi; jangan reka angka. Nyatakan jika data tidak mencukupi untuk sesuatu kesimpulan.
TXT;
        $prompt = "Statistik aduan (JSON):\n```json\n".json_encode($statistik, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n```";

        return $this->laksana($user, 'analisis_laporan', $sistem, $prompt, ['jenis' => 'analisis_laporan', 'statistik' => $statistik]);
    }

    // ------------------------------------------------------------------
    private function laksana(User $user, string $jenis, string $sistem, string $prompt, array $konteks, ?int $aduanId = null, ?Collection $rujukan = null): AiCadangan
    {
        $rekod = AiCadangan::create([
            'aduan_id' => $aduanId,
            'user_id' => $user->id,
            'jenis' => $jenis,
            'penyedia' => $this->provider->nama(),
            'model' => $this->provider->model(),
            'prompt' => $prompt,
            'rujukan_dipetik' => $rujukan?->map(fn ($r) => [
                'label' => $r['label'], 'seksyen_id' => $r['id'], 'tajuk' => $r['tajuk'],
            ])->values()->all(),
        ]);

        $mula = microtime(true);
        try {
            $res = $this->provider->jana($sistem, $prompt, $konteks);
            $rekod->update([
                'jawapan' => $res->teks,
                'model' => $res->model ?? $rekod->model,
                'metadata' => $res->metadata,
                'tempoh_ms' => (int) ((microtime(true) - $mula) * 1000),
            ]);
        } catch (\Throwable $e) {
            // BRS-024: kegagalan AI tidak menjejaskan kes; rekod ralat dan pegawai boleh cuba semula.
            $rekod->update([
                'ralat' => $e instanceof AiGagal ? $e->getMessage() : 'Ralat tidak dijangka: '.$e->getMessage(),
                'tempoh_ms' => (int) ((microtime(true) - $mula) * 1000),
            ]);
            report($e);
        }

        Audit::log('ai.jana', $rekod, "Jana {$jenis} melalui {$rekod->penyedia}", aduanId: $aduanId);

        return $rekod->fresh();
    }

    /** Ringkasan kes yang dihantar kepada AI. Maklumat pengadu TIDAK dihantar. */
    public function ringkasanKes(Aduan $aduan): array
    {
        $anonim = config('integriti.ai.anonimkan');
        $alias = [];
        $pihak = $aduan->pihakTerlibat->values()->map(function ($p, $i) use (&$alias, $anonim) {
            $nama = $anonim ? 'Pegawai '.chr(65 + $i) : $p->nama;
            $alias[$p->nama] = $nama;

            return [
                'nama' => $nama,
                'peranan' => AduanPihakTerlibat::PERANAN[$p->peranan] ?? $p->peranan,
                'jawatan' => $p->jawatan,
                'jabatan' => $p->jabatan,
            ];
        })->all();

        $tapis = function (?string $teks) use ($alias, $anonim) {
            $teks = (string) $teks;
            if ($anonim) {
                foreach ($alias as $asal => $ganti) {
                    $teks = str_ireplace($asal, $ganti, $teks);
                }
                // Buang nombor KP dan telefon yang mungkin tertulis dalam teks.
                $teks = preg_replace('/\b\d{6}-?\d{2}-?\d{4}\b/', '[NO.KP]', $teks);
                $teks = preg_replace('/\b(\+?6?01\d)[- ]?\d{3,4}[- ]?\d{4}\b/', '[TELEFON]', $teks);
            }

            return $teks;
        };

        return [
            'no_rujukan' => $aduan->no_rujukan,
            'kategori' => $aduan->kategori?->nama,
            'saluran' => $aduan->saluran?->nama,
            'tarikh_terima' => $aduan->tarikh_terima?->format('d/m/Y'),
            'tarikh_kejadian' => $aduan->tarikh_kejadian?->format('d/m/Y'),
            'tarikh_akhir_sla' => $aduan->tarikh_akhir_sla?->format('d/m/Y'),
            'baki_hari_sla' => $aduan->bakiHari(),
            'jabatan_terlibat' => $aduan->jabatanTerlibat?->nama,
            'lokasi' => $tapis($aduan->lokasi),
            'tajuk' => $tapis($aduan->tajuk),
            'keterangan' => $tapis($aduan->keterangan),
            'pihak_terlibat' => $pihak,
            'aktiviti' => $aduan->aktiviti->map(fn ($a) => [
                'tarikh' => $a->tarikh_aktiviti->format('d/m/Y'),
                'jenis' => SiasatanAktiviti::JENIS[$a->jenis] ?? $a->jenis,
                'tajuk' => $tapis($a->tajuk),
                'butiran' => Str::limit($tapis($a->butiran), 1500),
            ])->all(),
            'bukti' => $aduan->bukti->map(fn ($b) => [
                'no' => $b->no_bukti,
                'jenis' => Bukti::JENIS[$b->jenis] ?? $b->jenis,
                'tajuk' => $tapis($b->tajuk),
                'keterangan' => Str::limit($tapis($b->keterangan), 600),
                'status' => Bukti::STATUS[$b->status_pengesahan] ?? $b->status_pengesahan,
            ])->all(),
        ];
    }

    /**
     * Cari seksyen peraturan berkaitan (RAG ringkas menggunakan FULLTEXT MySQL).
     *
     * @return Collection<int, array{id:int,label:string,tajuk:string,sumber:string,kandungan:string}>
     */
    public function cariRujukan(Aduan $aduan): Collection
    {
        $had = config('integriti.ai.bilangan_rujukan');
        $teks = implode(' ', [
            $aduan->kategori?->nama, $aduan->kategori?->keterangan, $aduan->tajuk,
            Str::limit($aduan->keterangan, 1200, ''),
            $aduan->aktiviti->pluck('tajuk')->implode(' '),
        ]);
        $teks = trim(preg_replace('/[^\pL\pN\s]+/u', ' ', $teks));

        $q = RujukanSeksyen::with('rujukan')
            ->whereHas('rujukan', fn ($r) => $r->where('aktif', true));

        if (DB::getDriverName() === 'mysql' && $teks !== '') {
            $hasil = (clone $q)->select('rujukan_seksyen.*')
                ->selectRaw('MATCH(tajuk, kandungan) AGAINST (? IN NATURAL LANGUAGE MODE) AS skor', [$teks])
                ->whereRaw('MATCH(tajuk, kandungan) AGAINST (? IN NATURAL LANGUAGE MODE)', [$teks])
                ->orderByDesc('skor')->limit($had)->get();
        } else {
            $kata = collect(preg_split('/\s+/', mb_strtolower($teks)))->filter(fn ($w) => mb_strlen($w) > 4)->unique()->take(12);
            $hasil = (clone $q)->where(function ($w) use ($kata) {
                foreach ($kata as $k) {
                    $w->orWhere('kandungan', 'like', "%{$k}%")->orWhere('tajuk', 'like', "%{$k}%");
                }
            })->limit($had)->get();
        }

        // Sentiasa sertakan seksyen jenis hukuman supaya cadangan tindakan berasas.
        $hukuman = (clone $q)->where('tajuk', 'like', '%hukuman%')->limit(2)->get();
        $hasil = $hasil->concat($hukuman)->unique('id')->values();

        return $hasil->map(fn (RujukanSeksyen $s, $i) => [
            'id' => $s->id,
            'label' => 'R'.($i + 1),
            'tajuk' => $s->label(),
            'sumber' => $s->rujukan->teks_rasmi ? 'teks rasmi' : 'ringkasan (belum disahkan)',
            'kandungan' => Str::limit($s->kandungan, 2500),
        ]);
    }

    private function sistemKes(): string
    {
        return <<<'TXT'
Anda ialah pembantu analisis kes untuk Pegawai Siasatan, Unit Integriti, Jabatan Khidmat Pengurusan,
Majlis Bandaraya Seberang Perai (MBSP). Peranan anda ialah memberi PANDANGAN dan CADANGAN sahaja;
keputusan muktamad dibuat oleh Pegawai Siasatan dan diluluskan oleh Ketua Unit Integriti.

Peraturan:
1. Gunakan HANYA fakta kes dan rujukan peraturan yang diberikan. Jangan reka fakta, nombor peraturan atau pekeliling.
2. Setiap kali merujuk peraturan, petik label rujukan seperti [R1], [R2]. Jika tiada rujukan yang sesuai, nyatakan dengan jelas
   "Tiada peruntukan khusus dalam pustaka rujukan — perlu semakan manual".
3. Jika rujukan bertanda "ringkasan (belum disahkan)", ingatkan pegawai untuk menyemak teks rasmi.
4. Nilai kekuatan bukti secara objektif; nyatakan jurang bukti dengan jelas.
5. Pegawai MBSP tertakluk kepada Peraturan-Peraturan Pegawai Awam Majlis Perbandaran Seberang Perai (Kelakuan dan Tatatertib) 1995.
   Peraturan-Peraturan Pegawai Awam (Kelakuan dan Tatatertib) 1993 [P.U. (A) 395/1993] ialah peraturan perkhidmatan awam Persekutuan
   yang menjadi model peraturan MBSP; apabila memetiknya, nyatakan bahawa padanan peruntukan dalam peraturan MBSP 1995 perlu disemak.
6. Cadangan tindakan mesti dipilih daripada senarai "Jenis Tindakan" yang diberi (nyatakan kodnya).
7. Bersikap neutral; anggap pegawai yang diadu tidak bersalah sehingga dibuktikan.
8. Tulis dalam Bahasa Melayu yang formal dan ringkas.

Format jawapan (Markdown):
## 1. Ringkasan Fakta
## 2. Isu Utama
## 3. Peruntukan Berkaitan
## 4. Penilaian Bukti
## 5. Cadangan Tindakan
## 6. Langkah Siasatan Seterusnya
## 7. Risiko dan Perkara Perlu Diberi Perhatian
TXT;
    }

    private function binaPromptKes(array $kes, Collection $rujukan, array $jenisTindakan, ?string $soalan): string
    {
        $json = fn ($d) => json_encode($d, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        $rujukanTeks = $rujukan->map(fn ($r) => "[{$r['label']}] {$r['tajuk']} ({$r['sumber']})\n{$r['kandungan']}")->implode("\n\n---\n\n");
        if ($rujukanTeks === '') {
            $rujukanTeks = '(Tiada rujukan ditemui dalam pustaka.)';
        }

        $tindakanTeks = collect($jenisTindakan)->map(fn ($t) => "- {$t['kod']}: {$t['nama']}".($t['rujukan_peraturan'] ? " ({$t['rujukan_peraturan']})" : ''))->implode("\n");

        $p = "<kes>\n{$json($kes)}\n</kes>\n\n<rujukan_peraturan>\n{$rujukanTeks}\n</rujukan_peraturan>\n\n<jenis_tindakan>\n{$tindakanTeks}\n</jenis_tindakan>\n\n";
        $p .= 'Sila analisis kes di atas dan berikan pandangan mengikut format yang ditetapkan.';
        if ($soalan) {
            $p .= "\n\nSoalan tambahan daripada Pegawai Siasatan: ".$soalan;
        }

        return $p;
    }
}
