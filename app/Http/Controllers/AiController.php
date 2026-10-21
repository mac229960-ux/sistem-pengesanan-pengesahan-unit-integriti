<?php

namespace App\Http\Controllers;

use App\Models\Aduan;
use App\Models\AiCadangan;
use App\Services\Ai\AiService;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AiController extends Controller
{
    public function jana(Request $request, Aduan $aduan, AiService $ai)
    {
        $this->authorize('lihatSiasatan', $aduan);
        $data = $request->validate(['soalan' => ['nullable', 'string', 'max:2000']]);

        $rekod = $ai->cadanganKes($aduan, $request->user(), $data['soalan'] ?? null);

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'ai'])->with(
            $rekod->ralat ? 'ralat' : 'berjaya',
            $rekod->ralat ? 'Cadangan AI gagal dijana: '.$rekod->ralat.' Kes tidak terjejas; sila cuba semula.' : 'Cadangan AI berjaya dijana. Sila semak dan sahkan.'
        );
    }

    /** Pegawai menilai cadangan AI (kawalan manusia — BRS-019). */
    public function semak(Request $request, AiCadangan $ai)
    {
        $aduan = $ai->aduan;
        abort_unless($aduan, 404);
        $this->authorize('lihatSiasatan', $aduan);

        $data = $request->validate([
            'status_semakan' => ['required', Rule::in(['diterima', 'diterima_sebahagian', 'ditolak'])],
            'ulasan_pegawai' => ['nullable', 'string', 'max:3000'],
        ]);
        $ai->update($data + ['disemak_oleh' => $request->user()->id, 'disemak_pada' => now()]);
        Audit::perubahan('ai.semak', $ai, 'Semakan cadangan AI: '.AiCadangan::STATUS[$data['status_semakan']]);

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'ai'])->with('berjaya', 'Semakan cadangan AI direkodkan.');
    }
}
