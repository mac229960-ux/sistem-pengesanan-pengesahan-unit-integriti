<?php

namespace App\Http\Controllers;

use App\Enums\Peranan;
use App\Services\Hr\HrService;
use Illuminate\Http\Request;

/** Carian pegawai melalui Sistem HR Bersepadu (BRS-008). */
class PegawaiHrController extends Controller
{
    public function cari(Request $request, HrService $hr)
    {
        abort_unless($request->user()->ada(Peranan::PembantuTadbir, Peranan::PegawaiSiasatan, Peranan::KetuaUnit), 403);
        $kata = trim((string) $request->input('q'));
        if (mb_strlen($kata) < 2) {
            return response()->json([]);
        }

        try {
            return response()->json($hr->cari($kata)->values());
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['ralat' => 'Sistem HR tidak dapat dihubungi. Sila masukkan maklumat secara manual.'], 503);
        }
    }
}
