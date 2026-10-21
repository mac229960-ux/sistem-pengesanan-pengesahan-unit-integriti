<?php

namespace App\Http\Controllers;

use App\Enums\KeputusanSiasatan;
use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Services\AliranKerja;
use App\Services\Audit;
use App\Services\Notifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Rumusan dapatan & cadangan tindakan oleh Pegawai Siasatan (BRS-005, BRS-013).
 */
class CadanganTindakanController extends Controller
{
    public function simpan(Request $request, Aduan $aduan)
    {
        $this->authorize('siasat', $aduan);
        $data = $this->sahkan($request, $aduan);

        $cadangan = DB::transaction(function () use ($aduan, $data, $request) {
            $semasa = $aduan->cadanganTindakan()->first();
            // Draf sedia ada dikemas kini; jika versi terakhir telah dihantar/dikembalikan, cipta versi baharu.
            if ($semasa && $semasa->status === 'draf') {
                $semasa->update($data);
                $c = $semasa;
            } else {
                $c = $aduan->cadanganTindakan()->create($data + [
                    'versi' => ($semasa->versi ?? 0) + 1,
                    'disediakan_oleh' => $request->user()->id,
                    'status' => 'draf',
                ]);
            }
            $c->jenisTindakan()->sync($request->input('jenis_tindakan', []));

            return $c;
        });

        Audit::log('cadangan.simpan', $cadangan, "Simpan draf cadangan tindakan v{$cadangan->versi}");

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'cadangan'])->with('berjaya', 'Draf cadangan tindakan disimpan.');
    }

    public function hantar(Request $request, Aduan $aduan)
    {
        $this->authorize('siasat', $aduan);
        $cadangan = $aduan->cadanganTindakan()->first();
        abort_unless($cadangan && $cadangan->status === 'draf', 422, 'Tiada draf cadangan untuk dihantar.');

        if ($cadangan->jenisTindakan()->count() === 0) {
            return back()->with('ralat', 'Sila pilih sekurang-kurangnya satu jenis tindakan sebelum menghantar.');
        }
        if ($aduan->status === StatusAduan::Diagihkan) {
            AliranKerja::tukar($aduan, StatusAduan::DalamSiasatan, 'Siasatan dimulakan');
        }

        DB::transaction(function () use ($aduan, $cadangan) {
            $cadangan->update(['status' => 'dihantar', 'dihantar_pada' => now()]);
            AliranKerja::tukar($aduan, StatusAduan::MenungguKelulusan, "Cadangan tindakan v{$cadangan->versi} dihantar untuk kelulusan");
        });

        Notifikasi::hantar(Notifikasi::ketuaUnit(), $aduan, 'kelulusan', 'Cadangan tindakan menunggu kelulusan',
            "Pegawai Siasatan {$request->user()->name} telah menghantar cadangan tindakan bagi kes {$aduan->no_rujukan} untuk kelulusan anda.");

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'cadangan'])->with('berjaya', 'Cadangan tindakan dihantar kepada Ketua Unit Integriti untuk kelulusan.');
    }

    private function sahkan(Request $request, Aduan $aduan): array
    {
        $data = $request->validate([
            'keputusan_siasatan' => ['required', Rule::enum(KeputusanSiasatan::class)],
            'dapatan' => ['required', 'string', 'min:20'],
            'justifikasi' => ['required', 'string', 'min:10'],
            'peruntukan_peraturan' => ['nullable', 'string', 'max:255'],
            'ai_cadangan_id' => ['nullable', Rule::exists('ai_cadangan', 'id')->where('aduan_id', $aduan->id)],
            'jenis_tindakan' => ['nullable', 'array'],
            'jenis_tindakan.*' => ['integer', 'exists:jenis_tindakan,id'],
        ], [], ['dapatan' => 'dapatan siasatan', 'justifikasi' => 'justifikasi']);
        unset($data['jenis_tindakan']);

        return $data;
    }
}
