<?php

namespace App\Http\Controllers;

use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Services\AliranKerja;
use App\Services\Audit;
use App\Services\Notifikasi;
use Illuminate\Http\Request;

/**
 * Rekod tindakan yang diambil dan penutupan kes (BRS-011: kes selesai selepas tindakan diambil).
 */
class TindakanController extends Controller
{
    public function store(Request $request, Aduan $aduan)
    {
        $this->authorize('rekodTindakan', $aduan);
        $data = $request->validate([
            'jenis_tindakan_id' => ['required', 'exists:jenis_tindakan,id'],
            'tarikh_tindakan' => ['required', 'date', 'before_or_equal:today'],
            'no_rujukan_surat' => ['nullable', 'string', 'max:150'],
            'butiran' => ['required', 'string', 'max:5000'],
        ]);

        $t = $aduan->tindakanDiambil()->create($data + ['direkod_oleh' => $request->user()->id]);
        Audit::log('tindakan.rekod', $t, 'Rekod tindakan diambil', baru: $data);

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'cadangan'])->with('berjaya', 'Tindakan direkodkan.');
    }

    public function tutup(Request $request, Aduan $aduan)
    {
        $this->authorize('tutup', $aduan);
        if ($aduan->tindakanDiambil()->doesntExist()) {
            return back()->with('ralat', 'Sila rekod sekurang-kurangnya satu tindakan yang diambil sebelum menutup kes.');
        }
        $data = $request->validate(['catatan' => ['nullable', 'string', 'max:2000']]);

        AliranKerja::tukar($aduan, StatusAduan::Selesai, $data['catatan'] ?? 'Kes ditutup');

        Notifikasi::hantar($aduan->pegawaiSiasatan, $aduan, 'selesai', 'Kes telah ditutup',
            "Kes {$aduan->no_rujukan} telah ditutup sebagai selesai.".($aduan->selesaiDalamSla() ? ' Diselesaikan dalam tempoh SLA.' : ' Kes diselesaikan melebihi tempoh SLA.'));

        return redirect()->route('aduan.show', $aduan)->with('berjaya', 'Kes ditutup. Rekod akan disimpan sehingga '.$aduan->retensi_hingga->format('d/m/Y').'.');
    }
}
