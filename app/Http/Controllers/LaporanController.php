<?php

namespace App\Http\Controllers;

use App\Enums\Peranan;
use App\Enums\StatusAduan;
use App\Exports\LaporanAduanExport;
use App\Models\AiCadangan;
use App\Models\Jabatan;
use App\Models\KategoriAduan;
use App\Models\SaluranAduan;
use App\Models\User;
use App\Services\Ai\AiService;
use App\Services\Audit;
use App\Services\StatistikAduan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/** Laporan dan analisis (BRS-018, BRS-026). */
class LaporanController extends Controller
{
    public function index(Request $request)
    {
        [$penapis, $stat] = $this->stat($request);
        $user = $request->user();

        $kes = StatistikAduan::bolehLihatSenaraiKes($user)
            ? $stat->query()->with(['kategori', 'pegawaiSiasatan'])->orderByDesc('tarikh_terima')->paginate(25)->withQueryString()
            : null;

        return view('laporan.index', [
            'penapis' => $penapis,
            'ringkasan' => $stat->ringkasan(),
            'ikutStatus' => $stat->ikutStatus(),
            'ikutKategori' => $stat->ikutKategori(),
            'ikutSaluran' => $stat->ikutSaluran(),
            'ikutJabatan' => $stat->ikutJabatan(),
            'ikutKeputusan' => $stat->ikutKeputusan(),
            'ikutPegawai' => $user->isPegawaiSiasatan() ? [] : $stat->ikutPegawai(),
            'kes' => $kes,
            'analisis' => AiCadangan::where('user_id', $user->id)->where('jenis', 'analisis_laporan')->latest('id')->first(),
            'pilihan' => $this->pilihan($user),
        ]);
    }

    public function excel(Request $request)
    {
        [$penapis, $stat] = $this->stat($request);
        Audit::log('laporan.eksport', null, 'Eksport laporan Excel', baru: ['format' => 'xlsx', 'penapis' => $penapis]);

        $termasukKes = StatistikAduan::bolehLihatSenaraiKes($request->user());

        return Excel::download(new LaporanAduanExport($stat, $penapis, $termasukKes), 'laporan-aduan-integriti-'.now()->format('Ymd-His').'.xlsx');
    }

    public function pdf(Request $request)
    {
        [$penapis, $stat] = $this->stat($request);
        $user = $request->user();
        Audit::log('laporan.eksport', null, 'Eksport laporan PDF', baru: ['format' => 'pdf', 'penapis' => $penapis]);

        $pdf = Pdf::loadView('laporan.pdf', [
            'penapis' => $this->labelPenapis($penapis),
            'ringkasan' => $stat->ringkasan(),
            'ikutStatus' => $stat->ikutStatus(),
            'ikutKategori' => $stat->ikutKategori(),
            'ikutSaluran' => $stat->ikutSaluran(),
            'ikutJabatan' => $stat->ikutJabatan(),
            'ikutKeputusan' => $stat->ikutKeputusan(),
            'ikutPegawai' => $user->isPegawaiSiasatan() ? [] : $stat->ikutPegawai(),
            'kes' => StatistikAduan::bolehLihatSenaraiKes($user)
                ? $stat->query()->with(['kategori', 'pegawaiSiasatan'])->orderByDesc('tarikh_terima')->limit(500)->get()
                : null,
            'user' => $user,
        ])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-aduan-integriti-'.now()->format('Ymd-His').'.pdf');
    }

    public function analisisAi(Request $request)
    {
        [$penapis, $stat] = $this->stat($request);

        $hasil = app(AiService::class)->analisisLaporan($stat->untukAi(), $request->user());

        return redirect()->route('laporan.index', $penapis)->with(
            $hasil->ralat ? 'ralat' : 'berjaya',
            $hasil->ralat ? 'Analisis AI gagal: '.$hasil->ralat : 'Analisis AI telah dijana. Sila semak sebelum digunakan.'
        );
    }

    /** @return array{0: array, 1: StatistikAduan} */
    private function stat(Request $request): array
    {
        $request->validate([
            'dari' => ['nullable', 'date'],
            'hingga' => ['nullable', 'date', 'after_or_equal:dari'],
            'kategori_id' => ['nullable', 'integer'],
            'saluran_id' => ['nullable', 'integer'],
            'jabatan_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string'],
            'pegawai_id' => ['nullable', 'integer'],
        ]);
        $penapis = StatistikAduan::penapisDariRequest($request->all());

        return [$penapis, new StatistikAduan($request->user(), $penapis)];
    }

    private function pilihan(User $user): array
    {
        return [
            'kategori' => KategoriAduan::orderBy('nama')->pluck('nama', 'id'),
            'saluran' => SaluranAduan::orderBy('nama')->pluck('nama', 'id'),
            'jabatan' => Jabatan::orderBy('nama')->pluck('nama', 'id'),
            'status' => StatusAduan::options(),
            'pegawai' => $user->isPegawaiSiasatan()
                ? collect()
                : User::where('peranan', Peranan::PegawaiSiasatan)->orderBy('name')->pluck('name', 'id'),
        ];
    }

    private function labelPenapis(array $p): array
    {
        $label = [];
        if (isset($p['dari']) || isset($p['hingga'])) {
            $label['Tempoh'] = ($p['dari'] ?? '…').' hingga '.($p['hingga'] ?? '…');
        }
        if (isset($p['kategori_id'])) {
            $label['Kategori'] = KategoriAduan::find($p['kategori_id'])?->nama;
        }
        if (isset($p['saluran_id'])) {
            $label['Saluran'] = SaluranAduan::find($p['saluran_id'])?->nama;
        }
        if (isset($p['jabatan_id'])) {
            $label['Jabatan'] = Jabatan::find($p['jabatan_id'])?->nama;
        }
        if (isset($p['status'])) {
            $label['Status'] = StatusAduan::tryFrom($p['status'])?->label();
        }
        if (isset($p['pegawai_id'])) {
            $label['Pegawai Siasatan'] = User::find($p['pegawai_id'])?->name;
        }

        return array_filter($label);
    }
}
