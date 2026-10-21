<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PegawaiHr;
use Illuminate\Http\Request;

/** Paparan baca sahaja data cermin Sistem HR Bersepadu (mock buat masa ini). */
class PegawaiHrController extends Controller
{
    public function index(Request $request)
    {
        $cari = trim((string) $request->input('cari'));

        $pegawai = PegawaiHr::with('jabatan')
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('nama', 'like', "%{$cari}%")
                ->orWhere('no_pekerja', 'like', "%{$cari}%")
                ->orWhere('jawatan', 'like', "%{$cari}%")))
            ->orderBy('nama')->paginate(25)->withQueryString();

        return view('admin.pegawai-hr.index', [
            'pegawai' => $pegawai,
            'driver' => config('integriti.hr.driver'),
        ]);
    }
}
