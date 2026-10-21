<?php

namespace App\Http\Controllers;

use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\SiasatanAktiviti;
use App\Services\AliranKerja;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SiasatanAktivitiController extends Controller
{
    public function store(Request $request, Aduan $aduan)
    {
        $this->authorize('siasat', $aduan);
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(SiasatanAktiviti::JENIS))],
            'tarikh_aktiviti' => ['required', 'date', 'before_or_equal:now'],
            'tajuk' => ['required', 'string', 'max:255'],
            'butiran' => ['required', 'string'],
        ]);

        // Aktiviti pertama secara automatik memulakan siasatan.
        if ($aduan->status === StatusAduan::Diagihkan) {
            AliranKerja::tukar($aduan, StatusAduan::DalamSiasatan, 'Siasatan dimulakan');
        }

        $a = $aduan->aktiviti()->create($data + ['user_id' => $request->user()->id]);
        Audit::log('aktiviti.tambah', $a, 'Rekod aktiviti siasatan: '.$a->tajuk);

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'siasatan'])->with('berjaya', 'Aktiviti siasatan direkodkan.');
    }

    public function destroy(Request $request, Aduan $aduan, SiasatanAktiviti $aktiviti)
    {
        abort_unless($aktiviti->aduan_id === $aduan->id, 404);
        $this->authorize('siasat', $aduan);
        abort_unless($aktiviti->user_id === $request->user()->id || $request->user()->isKetuaUnit(), 403);

        Audit::log('aktiviti.hapus', $aktiviti, 'Hapus aktiviti: '.$aktiviti->tajuk, lama: $aktiviti->toArray());
        $aktiviti->delete();

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'siasatan'])->with('berjaya', 'Aktiviti dihapuskan.');
    }
}
