<?php

namespace App\Http\Controllers;

use App\Enums\Peranan;
use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\AuditLog;
use App\Models\RujukanPeraturan;
use App\Models\User;
use App\Services\StatistikAduan;
use Illuminate\Http\Request;

/** Dashboard mengikut peranan (BRS-026). */
class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->isPentadbir()) {
            return view('dashboard', [
                'mod' => 'pentadbir',
                'sistem' => [
                    'pengguna_aktif' => User::aktif()->count(),
                    'pengguna_jumlah' => User::count(),
                    'ikut_peranan' => collect(Peranan::cases())->mapWithKeys(fn ($p) => [$p->label() => User::aktif()->where('peranan', $p->value)->count()])->all(),
                    'rujukan' => RujukanPeraturan::where('aktif', true)->count(),
                    'rujukan_rasmi' => RujukanPeraturan::where('aktif', true)->where('teks_rasmi', true)->count(),
                    'audit_hari_ini' => AuditLog::whereDate('created_at', today())->count(),
                    'log_masuk_gagal' => AuditLog::whereDate('created_at', today())->whereIn('tindakan', ['auth.gagal', 'auth.otp_gagal'])->count(),
                    'penyedia_ai' => config('integriti.ai.provider'),
                    'hr_driver' => config('integriti.hr.driver'),
                ],
                'auditTerkini' => AuditLog::with('pengguna')->latest('id')->limit(10)->get(),
            ]);
        }

        $stat = new StatistikAduan($user);
        $data = [
            'mod' => $user->peranan->value,
            'ringkasan' => $stat->ringkasan(),
            'ikutStatus' => $stat->ikutStatus(),
            'ikutKategori' => $stat->ikutKategori(),
            'trend' => $stat->trendBulanan(),
            'bolehLihatKes' => $user->ada(Peranan::KetuaUnit, Peranan::PegawaiSiasatan, Peranan::PembantuTadbir),
        ];

        if ($user->ada(Peranan::KetuaUnit, Peranan::PegawaiSiasatan)) {
            $data['kesSla'] = $stat->kesKritikalSla();
        }

        if ($user->isPegawaiSiasatan()) {
            $data['tugasan'] = Aduan::bolehDilihat($user)->aktif()
                ->with('kategori')->orderBy('tarikh_akhir_sla')->limit(15)->get();
        }

        if ($user->isKetuaUnit()) {
            $data['menungguKelulusan'] = Aduan::where('status', StatusAduan::MenungguKelulusan)->count();
            $data['belumDiagih'] = Aduan::where('status', StatusAduan::Baru)->with('kategori')->orderBy('tarikh_terima')->limit(10)->get();
        }

        if ($user->peranan === Peranan::PembantuTadbir) {
            $data['terkini'] = Aduan::with(['kategori', 'saluran'])->latest('id')->limit(10)->get();
        }

        return view('dashboard', $data);
    }
}
