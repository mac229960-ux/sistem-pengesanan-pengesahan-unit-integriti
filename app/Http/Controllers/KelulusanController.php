<?php

namespace App\Http\Controllers;

use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\Kelulusan;
use App\Services\AliranKerja;
use App\Services\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Kelulusan manual oleh Ketua Unit Integriti sahaja (BRS-013, BRS-021). Tiada kelulusan automatik.
 */
class KelulusanController extends Controller
{
    public function index(Request $request)
    {
        abort_unless($request->user()->isKetuaUnit(), 403);

        $senarai = Aduan::with(['kategori', 'pegawaiSiasatan', 'cadanganTerkini.jenisTindakan'])
            ->where('status', StatusAduan::MenungguKelulusan)
            ->orderBy('tarikh_akhir_sla')->get();

        $terkini = Kelulusan::with(['pelulus', 'cadangan.aduan'])->latest()->limit(15)->get();

        return view('kelulusan.index', compact('senarai', 'terkini'));
    }

    public function store(Request $request, Aduan $aduan)
    {
        $this->authorize('lulus', $aduan);
        $data = $request->validate([
            'keputusan' => ['required', 'in:lulus,kembalikan'],
            'ulasan' => ['nullable', 'required_if:keputusan,kembalikan', 'string', 'max:3000'],
        ], ['ulasan.required_if' => 'Ulasan wajib diisi jika cadangan dikembalikan.']);

        $cadangan = $aduan->cadanganTindakan()->first();
        abort_unless($cadangan && $cadangan->status === 'dihantar', 422);

        DB::transaction(function () use ($aduan, $cadangan, $data, $request) {
            Kelulusan::create([
                'cadangan_tindakan_id' => $cadangan->id, 'aduan_id' => $aduan->id,
                'pelulus_id' => $request->user()->id, 'keputusan' => $data['keputusan'], 'ulasan' => $data['ulasan'] ?? null,
            ]);

            if ($data['keputusan'] === 'lulus') {
                $cadangan->update(['status' => 'diluluskan']);
                AliranKerja::tukar($aduan, StatusAduan::Diluluskan, $data['ulasan'] ?? 'Cadangan diluluskan', [
                    'keputusan_siasatan' => $cadangan->keputusan_siasatan,
                ]);
            } else {
                $cadangan->update(['status' => 'dikembalikan']);
                AliranKerja::tukar($aduan, StatusAduan::Dikembalikan, $data['ulasan']);
            }
        });

        $lulus = $data['keputusan'] === 'lulus';
        Notifikasi::hantar($aduan->pegawaiSiasatan, $aduan, 'keputusan_kelulusan',
            $lulus ? 'Cadangan tindakan diluluskan' : 'Cadangan tindakan dikembalikan',
            $lulus
                ? "Cadangan tindakan bagi kes {$aduan->no_rujukan} telah diluluskan. Sila laksanakan dan rekod tindakan yang diambil."
                : "Cadangan tindakan bagi kes {$aduan->no_rujukan} dikembalikan untuk pindaan. Ulasan: {$data['ulasan']}");

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'cadangan'])
            ->with('berjaya', $lulus ? 'Cadangan diluluskan.' : 'Cadangan dikembalikan kepada Pegawai Siasatan.');
    }
}
