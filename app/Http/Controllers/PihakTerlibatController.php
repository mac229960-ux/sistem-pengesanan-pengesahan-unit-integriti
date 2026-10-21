<?php

namespace App\Http\Controllers;

use App\Models\Aduan;
use App\Models\AduanPihakTerlibat;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PihakTerlibatController extends Controller
{
    public function store(Request $request, Aduan $aduan)
    {
        abort_unless($request->user()->can('siasat', $aduan) || $request->user()->can('update', $aduan), 403);

        $data = $request->validate([
            'pegawai_hr_id' => ['nullable', 'exists:pegawai_hr,id'],
            'nama' => ['required', 'string', 'max:200'],
            'no_pekerja' => ['nullable', 'string', 'max:30'],
            'jawatan' => ['nullable', 'string', 'max:200'],
            'jabatan' => ['nullable', 'string', 'max:200'],
            'peranan' => ['required', Rule::in(array_keys(AduanPihakTerlibat::PERANAN))],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);

        $p = $aduan->pihakTerlibat()->create($data);
        Audit::log('pihak.tambah', $p, 'Tambah pihak terlibat: '.$p->nama, baru: $data);

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'maklumat'])->with('berjaya', 'Pihak terlibat ditambah.');
    }

    public function destroy(Request $request, Aduan $aduan, AduanPihakTerlibat $pihak)
    {
        abort_unless($pihak->aduan_id === $aduan->id, 404);
        abort_unless($request->user()->can('siasat', $aduan) || $request->user()->can('update', $aduan), 403);

        Audit::log('pihak.hapus', $pihak, 'Hapus pihak terlibat: '.$pihak->nama, lama: $pihak->toArray());
        $pihak->delete();

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'maklumat'])->with('berjaya', 'Pihak terlibat dihapuskan.');
    }
}
