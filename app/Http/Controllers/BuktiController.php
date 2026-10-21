<?php

namespace App\Http\Controllers;

use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\Bukti;
use App\Services\AliranKerja;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Bukti / bahan kes. Fail disimpan dalam storan peribadi, disulitkan, dan
 * dicap cincang SHA-256 untuk pengesahan integriti (rantaian jagaan).
 */
class BuktiController extends Controller
{
    public function store(Request $request, Aduan $aduan)
    {
        $this->authorize('siasat', $aduan);
        $cfg = config('integriti.bukti');
        $data = $request->validate([
            'jenis' => ['required', Rule::in(array_keys(Bukti::JENIS))],
            'tajuk' => ['required', 'string', 'max:255'],
            'keterangan' => ['nullable', 'string', 'max:5000'],
            'sumber' => ['nullable', 'string', 'max:255'],
            'tarikh_diperoleh' => ['nullable', 'date', 'before_or_equal:today'],
            'fail' => ['nullable', 'file', 'max:'.$cfg['saiz_maks_kb'], 'mimes:'.$cfg['mimes']],
        ]);

        if ($aduan->status === StatusAduan::Diagihkan) {
            AliranKerja::tukar($aduan, StatusAduan::DalamSiasatan, 'Siasatan dimulakan');
        }

        $bukti = DB::transaction(function () use ($request, $aduan, $data) {
            $bil = Bukti::withTrashed()->where('aduan_id', $aduan->id)->lockForUpdate()->count();
            $bukti = new Bukti($data + [
                'aduan_id' => $aduan->id,
                'no_bukti' => 'B'.str_pad((string) ($bil + 1), 3, '0', STR_PAD_LEFT),
                'dimuatnaik_oleh' => $request->user()->id,
            ]);

            if ($fail = $request->file('fail')) {
                $kandungan = $fail->get();
                $laluan = 'bukti/'.$aduan->id.'/'.Str::uuid().'.enc';
                Storage::disk('local')->put($laluan, Crypt::encryptString(base64_encode($kandungan)));
                $bukti->fill([
                    'nama_fail_asal' => $fail->getClientOriginalName(),
                    'laluan_fail' => $laluan,
                    'mime' => $fail->getMimeType(),
                    'saiz' => $fail->getSize(),
                    'sha256' => hash('sha256', $kandungan),
                ]);
            }
            $bukti->save();

            return $bukti;
        });

        Audit::log('bukti.tambah', $bukti, "Tambah bukti {$bukti->no_bukti}: {$bukti->tajuk}", baru: $bukti->only(['no_bukti', 'jenis', 'tajuk', 'nama_fail_asal', 'sha256']));

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'bukti'])->with('berjaya', "Bukti {$bukti->no_bukti} disimpan.");
    }

    public function muatTurun(Aduan $aduan, Bukti $bukti)
    {
        abort_unless($bukti->aduan_id === $aduan->id && $bukti->laluan_fail, 404);
        $this->authorize('lihatSiasatan', $aduan);

        $kandungan = base64_decode(Crypt::decryptString(Storage::disk('local')->get($bukti->laluan_fail)));
        if (hash('sha256', $kandungan) !== $bukti->sha256) {
            Audit::log('bukti.integriti_gagal', $bukti, 'Cincang SHA-256 tidak sepadan!');
            abort(409, 'Integriti fail bukti tidak dapat disahkan (cincang tidak sepadan). Sila maklumkan Pentadbir Sistem.');
        }

        Audit::log('bukti.muat_turun', $bukti, "Muat turun bukti {$bukti->no_bukti}");

        return response($kandungan, 200, [
            'Content-Type' => $bukti->mime ?: 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="'.addslashes($bukti->nama_fail_asal).'"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function sahkan(Request $request, Aduan $aduan, Bukti $bukti)
    {
        abort_unless($bukti->aduan_id === $aduan->id, 404);
        $this->authorize('siasat', $aduan);
        $data = $request->validate([
            'status_pengesahan' => ['required', Rule::in(['disahkan', 'ditolak', 'belum_disahkan'])],
            'catatan_pengesahan' => ['nullable', 'string', 'max:2000'],
        ]);

        $bukti->update($data + [
            'disahkan_oleh' => $data['status_pengesahan'] === 'belum_disahkan' ? null : $request->user()->id,
            'tarikh_disahkan' => $data['status_pengesahan'] === 'belum_disahkan' ? null : now(),
        ]);
        Audit::perubahan('bukti.sahkan', $bukti, "Pengesahan bukti {$bukti->no_bukti}: ".Bukti::STATUS[$data['status_pengesahan']]);

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'bukti'])->with('berjaya', "Status bukti {$bukti->no_bukti} dikemas kini.");
    }

    public function destroy(Request $request, Aduan $aduan, Bukti $bukti)
    {
        abort_unless($bukti->aduan_id === $aduan->id, 404);
        $this->authorize('siasat', $aduan);
        abort_unless($request->user()->isKetuaUnit() || $bukti->dimuatnaik_oleh === $request->user()->id, 403);

        // Soft delete sahaja — fail dikekalkan untuk tujuan audit.
        $bukti->delete();
        Audit::log('bukti.hapus', $bukti, "Hapus (arkib) bukti {$bukti->no_bukti}");

        return redirect()->route('aduan.show', [$aduan, 'tab' => 'bukti'])->with('berjaya', "Bukti {$bukti->no_bukti} dikeluarkan daripada senarai (rekod audit dikekalkan).");
    }
}
