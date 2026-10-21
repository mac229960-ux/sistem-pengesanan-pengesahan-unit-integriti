<?php

namespace App\Services;

use App\Enums\KeputusanSiasatan;
use App\Enums\Peranan;
use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Pengiraan statistik aduan (BRS-010, BRS-026) — dikongsi oleh dashboard, laporan dan eksport.
 * Semua output adalah agregat; tiada maklumat peribadi pengadu.
 */
class StatistikAduan
{
    public const MEDAN_PENAPIS = ['dari', 'hingga', 'kategori_id', 'saluran_id', 'jabatan_id', 'status', 'pegawai_id'];

    public function __construct(private User $user, private array $penapis = []) {}

    /** Pertanyaan asas mengikut penapis dan had akses pengguna. */
    public function query(): Builder
    {
        $p = $this->penapis;

        return Aduan::query()
            ->bolehDilihat($this->user)
            ->when($p['dari'] ?? null, fn ($q, $v) => $q->whereDate('tarikh_terima', '>=', $v))
            ->when($p['hingga'] ?? null, fn ($q, $v) => $q->whereDate('tarikh_terima', '<=', $v))
            ->when($p['kategori_id'] ?? null, fn ($q, $v) => $q->where('kategori_id', $v))
            ->when($p['saluran_id'] ?? null, fn ($q, $v) => $q->where('saluran_id', $v))
            ->when($p['jabatan_id'] ?? null, fn ($q, $v) => $q->where('jabatan_terlibat_id', $v))
            ->when($p['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($p['pegawai_id'] ?? null, fn ($q, $v) => $q->where('pegawai_siasatan_id', $v));
    }

    public function ringkasan(): array
    {
        $aktif = StatusAduan::aktifValues();
        $hariIni = today()->toDateString();
        $had30 = today()->addDays(30)->toDateString();
        $tutup = [StatusAduan::Selesai->value, StatusAduan::TidakDiteruskan->value];

        $r = $this->query()->toBase()->selectRaw('
            COUNT(*) AS jumlah,
            SUM(status IN ('.$this->ph($aktif).')) AS aktif,
            SUM(status = ?) AS baru,
            SUM(status IN (?, ?)) AS dalam_siasatan,
            SUM(status = ?) AS menunggu_kelulusan,
            SUM(status IN ('.$this->ph($aktif).') AND tarikh_akhir_sla >= ? AND tarikh_akhir_sla <= ?) AS hampir_sla,
            SUM(status IN ('.$this->ph($aktif).') AND tarikh_akhir_sla < ?) AS lewat,
            SUM(status IN (?, ?) AND YEAR(tarikh_selesai) = ?) AS selesai_tahun_ini,
            SUM(status IN (?, ?)) AS ditutup,
            SUM(status IN (?, ?) AND DATE(tarikh_selesai) <= tarikh_akhir_sla) AS ditutup_dalam_sla
        ', [
            ...$aktif,
            StatusAduan::Baru->value,
            StatusAduan::DalamSiasatan->value, StatusAduan::Dikembalikan->value,
            StatusAduan::MenungguKelulusan->value,
            ...$aktif, $hariIni, $had30,
            ...$aktif, $hariIni,
            ...$tutup, now()->year,
            ...$tutup,
            ...$tutup,
        ])->first();

        $r = array_map('intval', (array) $r);
        $r['peratus_dalam_sla'] = $r['ditutup'] > 0 ? round($r['ditutup_dalam_sla'] / $r['ditutup'] * 100, 1) : null;

        return $r;
    }

    /** @return array<string,int> label => bilangan */
    public function ikutStatus(): array
    {
        $data = $this->query()->toBase()->select('status', DB::raw('COUNT(*) AS n'))->groupBy('status')->pluck('n', 'status');

        return collect(StatusAduan::cases())
            ->mapWithKeys(fn ($s) => [$s->label() => (int) ($data[$s->value] ?? 0)])->all();
    }

    public function ikutKategori(): array
    {
        return $this->kumpul('kategori_aduan', 'kategori_id');
    }

    public function ikutSaluran(): array
    {
        return $this->kumpul('saluran_aduan', 'saluran_id');
    }

    public function ikutJabatan(): array
    {
        return $this->kumpul('jabatan', 'jabatan_terlibat_id', 'Tidak dinyatakan');
    }

    public function ikutKeputusan(): array
    {
        $data = $this->query()->toBase()->whereNotNull('keputusan_siasatan')
            ->select('keputusan_siasatan', DB::raw('COUNT(*) AS n'))->groupBy('keputusan_siasatan')->pluck('n', 'keputusan_siasatan');

        return collect(KeputusanSiasatan::cases())
            ->mapWithKeys(fn ($k) => [$k->label() => (int) ($data[$k->value] ?? 0)])->all();
    }

    /** Beban kerja dan pematuhan SLA setiap Pegawai Siasatan. */
    public function ikutPegawai(): array
    {
        $aktif = StatusAduan::aktifValues();
        $tutup = [StatusAduan::Selesai->value, StatusAduan::TidakDiteruskan->value];

        return $this->query()->toBase()
            ->join('users', 'users.id', '=', 'aduan.pegawai_siasatan_id')
            ->selectRaw('users.name AS nama, COUNT(*) AS jumlah,
                SUM(aduan.status IN ('.$this->ph($aktif).')) AS aktif,
                SUM(aduan.status IN ('.$this->ph($aktif).') AND aduan.tarikh_akhir_sla < ?) AS lewat,
                SUM(aduan.status IN (?, ?)) AS ditutup,
                SUM(aduan.status IN (?, ?) AND DATE(aduan.tarikh_selesai) <= aduan.tarikh_akhir_sla) AS dalam_sla',
                [...$aktif, ...$aktif, today()->toDateString(), ...$tutup, ...$tutup])
            ->groupBy('users.id', 'users.name')->orderBy('users.name')->get()
            ->map(function ($r) {
                $r = array_map(fn ($v) => is_numeric($v) ? (int) $v : $v, (array) $r);
                $r['peratus_sla'] = $r['ditutup'] > 0 ? round($r['dalam_sla'] / $r['ditutup'] * 100, 1) : null;

                return $r;
            })->all();
    }

    /** Trend 12 bulan: aduan diterima vs ditutup. */
    public function trendBulanan(int $bulan = 12): array
    {
        $mula = now()->startOfMonth()->subMonths($bulan - 1);
        $terima = $this->query()->toBase()->whereDate('tarikh_terima', '>=', $mula)
            ->selectRaw("DATE_FORMAT(tarikh_terima, '%Y-%m') AS b, COUNT(*) AS n")->groupBy('b')->pluck('n', 'b');
        $tutup = $this->query()->toBase()->whereNotNull('tarikh_selesai')->where('tarikh_selesai', '>=', $mula)
            ->selectRaw("DATE_FORMAT(tarikh_selesai, '%Y-%m') AS b, COUNT(*) AS n")->groupBy('b')->pluck('n', 'b');

        $hasil = ['label' => [], 'diterima' => [], 'selesai' => []];
        for ($i = 0; $i < $bulan; $i++) {
            $b = $mula->copy()->addMonths($i);
            $k = $b->format('Y-m');
            $hasil['label'][] = $b->translatedFormat('M Y');
            $hasil['diterima'][] = (int) ($terima[$k] ?? 0);
            $hasil['selesai'][] = (int) ($tutup[$k] ?? 0);
        }

        return $hasil;
    }

    /** Kes aktif yang hampir tamat (≤30 hari) atau telah melepasi SLA. */
    public function kesKritikalSla(int $had = 10)
    {
        return $this->query()->aktif()
            ->whereDate('tarikh_akhir_sla', '<=', today()->addDays(30))
            ->with(['kategori', 'pegawaiSiasatan'])
            ->orderBy('tarikh_akhir_sla')->limit($had)->get();
    }

    /** Statistik agregat untuk analisis AI — tiada nama atau data peribadi. */
    public function untukAi(): array
    {
        $r = $this->ringkasan();

        return [
            'tempoh' => [
                'dari' => $this->penapis['dari'] ?? 'semua',
                'hingga' => $this->penapis['hingga'] ?? 'semua',
            ],
            'sla_bulan' => config('integriti.sla_bulan'),
            'jumlah' => $r['jumlah'],
            'aktif' => $r['aktif'],
            'lewat' => $r['lewat'],
            'hampir_tamat_sla_30_hari' => $r['hampir_sla'],
            'ditutup' => $r['ditutup'],
            'peratus_dalam_sla' => $r['peratus_dalam_sla'],
            'ikut_status' => $this->ikutStatus(),
            'ikut_kategori' => $this->ikutKategori(),
            'ikut_saluran' => $this->ikutSaluran(),
            'ikut_jabatan' => $this->ikutJabatan(),
            'ikut_keputusan' => $this->ikutKeputusan(),
            'trend_bulanan' => $this->trendBulanan(),
            // Pegawai dianonimkan.
            'beban_pegawai' => collect($this->ikutPegawai())->values()
                ->map(fn ($p, $i) => ['pegawai' => 'Pegawai '.($i + 1)] + array_diff_key($p, ['nama' => 1]))->all(),
        ];
    }

    public static function penapisDariRequest(array $input): array
    {
        return array_filter(array_intersect_key($input, array_flip(self::MEDAN_PENAPIS)), fn ($v) => $v !== null && $v !== '');
    }

    public static function bolehLihatSenaraiKes(User $u): bool
    {
        return $u->ada(Peranan::KetuaUnit, Peranan::PegawaiSiasatan);
    }

    private function kumpul(string $jadual, string $fk, string $tiada = '-'): array
    {
        return $this->query()->toBase()
            ->leftJoin($jadual, "{$jadual}.id", '=', "aduan.{$fk}")
            ->selectRaw("COALESCE({$jadual}.nama, ?) AS nama, COUNT(*) AS n", [$tiada])
            ->groupBy('nama')->orderByDesc('n')->pluck('n', 'nama')->map(fn ($n) => (int) $n)->all();
    }

    private function ph(array $a): string
    {
        return implode(',', array_fill(0, count($a), '?'));
    }
}
