<?php

namespace App\Http\Controllers;

use App\Enums\Peranan;
use App\Models\RujukanPeraturan;
use App\Models\RujukanSeksyen;
use App\Services\Audit;
use App\Services\PemprosesDokumen;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Pustaka peraturan / pekeliling — sumber rujukan enjin cadangan AI (BRS-007, BRS-020, BRS-028).
 */
class RujukanController extends Controller
{
    public function __construct(private PemprosesDokumen $pemproses) {}

    public function index(Request $request)
    {
        $cari = trim((string) $request->input('cari'));

        $rujukan = RujukanPeraturan::withCount('seksyen')
            ->when($request->filled('jenis'), fn ($q) => $q->where('jenis', $request->input('jenis')))
            ->when($cari !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('tajuk', 'like', "%{$cari}%")
                ->orWhere('kod', 'like', "%{$cari}%")
                ->orWhere('ringkasan', 'like', "%{$cari}%")
                ->orWhereHas('seksyen', fn ($s) => $s->where('kandungan', 'like', "%{$cari}%")->orWhere('tajuk', 'like', "%{$cari}%"))))
            ->orderBy('jenis')->orderBy('tajuk')
            ->paginate(20)->withQueryString();

        return view('rujukan.index', [
            'rujukan' => $rujukan,
            'bolehUrus' => $this->bolehUrus(),
        ]);
    }

    public function show(Request $request, RujukanPeraturan $rujukan)
    {
        $rujukan->load('seksyen');

        return view('rujukan.show', ['rujukan' => $rujukan, 'bolehUrus' => $this->bolehUrus()]);
    }

    /** Muat turun fail sumber asal (cth. PDF Warta). */
    public function fail(RujukanPeraturan $rujukan)
    {
        abort_unless($rujukan->laluan_fail && Storage::disk('local')->exists($rujukan->laluan_fail), 404);
        Audit::log('rujukan.muat_turun', $rujukan, 'Muat turun fail rujukan: '.$rujukan->nama_fail);

        return Storage::disk('local')->download($rujukan->laluan_fail, $rujukan->nama_fail);
    }

    public function create()
    {
        return view('rujukan.borang', ['rujukan' => new RujukanPeraturan(['aktif' => true])]);
    }

    public function store(Request $request)
    {
        $data = $this->sahkan($request);

        $rujukan = DB::transaction(function () use ($request, $data) {
            $rujukan = RujukanPeraturan::create($data + ['dikemaskini_oleh' => $request->user()->id]);
            $bil = $this->prosesFail($request, $rujukan, false);
            Audit::log('rujukan.cipta', $rujukan, "Tambah rujukan {$rujukan->kod}".($bil !== null ? " ({$bil} seksyen diekstrak)" : ''), baru: $rujukan->only(['kod', 'tajuk', 'jenis']));

            return $rujukan;
        });

        return redirect()->route('rujukan.show', $rujukan)->with('berjaya', 'Rujukan berjaya ditambah.');
    }

    public function edit(RujukanPeraturan $rujukan)
    {
        return view('rujukan.borang', ['rujukan' => $rujukan]);
    }

    public function update(Request $request, RujukanPeraturan $rujukan)
    {
        $data = $this->sahkan($request, $rujukan);

        DB::transaction(function () use ($request, $rujukan, $data) {
            $rujukan->fill($data + ['dikemaskini_oleh' => $request->user()->id])->save();
            Audit::perubahan('rujukan.kemaskini', $rujukan, "Kemas kini rujukan {$rujukan->kod}");
            $bil = $this->prosesFail($request, $rujukan, $request->boolean('gantikan_seksyen'));
            if ($bil !== null) {
                Audit::log('rujukan.muat_naik', $rujukan, "Muat naik fail {$rujukan->nama_fail}: {$bil} seksyen diekstrak");
            }
        });

        return redirect()->route('rujukan.show', $rujukan)->with('berjaya', 'Rujukan berjaya dikemas kini.');
    }

    public function destroy(RujukanPeraturan $rujukan)
    {
        if ($rujukan->laluan_fail) {
            Storage::disk('local')->delete($rujukan->laluan_fail);
        }
        Audit::log('rujukan.hapus', $rujukan, "Hapus rujukan {$rujukan->kod}", lama: $rujukan->only(['kod', 'tajuk', 'jenis']));
        $rujukan->delete();

        return redirect()->route('rujukan.index')->with('berjaya', 'Rujukan telah dihapuskan.');
    }

    // ---------- Seksyen ----------
    public function simpanSeksyen(Request $request, RujukanPeraturan $rujukan)
    {
        $data = $this->sahkanSeksyen($request);
        $data['susunan'] ??= ((int) $rujukan->seksyen()->max('susunan')) + 1;
        $seksyen = $rujukan->seksyen()->create($data);
        Audit::log('rujukan.seksyen_tambah', $seksyen, "Tambah seksyen pada {$rujukan->kod}", baru: $seksyen->only(['no_seksyen', 'tajuk']));

        return back()->with('berjaya', 'Seksyen berjaya ditambah.');
    }

    public function kemaskiniSeksyen(Request $request, RujukanPeraturan $rujukan, RujukanSeksyen $seksyen)
    {
        abort_unless($seksyen->rujukan_id === $rujukan->id, 404);
        $seksyen->fill($this->sahkanSeksyen($request))->save();
        Audit::perubahan('rujukan.seksyen_kemaskini', $seksyen, "Kemas kini seksyen pada {$rujukan->kod}");

        return back()->with('berjaya', 'Seksyen berjaya dikemas kini.');
    }

    public function hapusSeksyen(RujukanPeraturan $rujukan, RujukanSeksyen $seksyen)
    {
        abort_unless($seksyen->rujukan_id === $rujukan->id, 404);
        Audit::log('rujukan.seksyen_hapus', $seksyen, "Hapus seksyen pada {$rujukan->kod}", lama: $seksyen->only(['no_seksyen', 'tajuk']));
        $seksyen->delete();

        return back()->with('berjaya', 'Seksyen telah dihapuskan.');
    }

    // ------------------------------------------------------------------
    private function sahkan(Request $request, ?RujukanPeraturan $rujukan = null): array
    {
        $data = $request->validate([
            'kod' => ['required', 'string', 'max:50', 'regex:/^[A-Za-z0-9\-_.\/]+$/', Rule::unique('rujukan_peraturan', 'kod')->ignore($rujukan)],
            'tajuk' => ['required', 'string', 'max:255'],
            'jenis' => ['required', Rule::in(array_keys(RujukanPeraturan::JENIS))],
            'penerbit' => ['nullable', 'string', 'max:255'],
            'tahun' => ['nullable', 'integer', 'min:1900', 'max:'.(date('Y') + 1)],
            'sumber_url' => ['nullable', 'url', 'max:255'],
            'ringkasan' => ['nullable', 'string', 'max:5000'],
            'fail' => ['nullable', 'file', 'mimes:pdf,txt', 'max:20480'],
        ], [], ['kod' => 'kod', 'fail' => 'fail dokumen']);

        unset($data['fail']);
        $data['teks_rasmi'] = $request->boolean('teks_rasmi');
        $data['aktif'] = $request->boolean('aktif');

        return $data;
    }

    private function sahkanSeksyen(Request $request): array
    {
        return $request->validate([
            'no_seksyen' => ['nullable', 'string', 'max:50'],
            'tajuk' => ['nullable', 'string', 'max:255'],
            'kandungan' => ['required', 'string', 'max:60000'],
            'susunan' => ['nullable', 'integer', 'min:0'],
        ]);
    }

    /** Simpan fail & ekstrak seksyen. Pulangkan bilangan seksyen dicipta, atau null jika tiada fail. */
    private function prosesFail(Request $request, RujukanPeraturan $rujukan, bool $gantikan): ?int
    {
        if (! $request->hasFile('fail')) {
            return null;
        }

        $fail = $request->file('fail');
        try {
            $teks = $this->pemproses->ekstrak($fail);
        } catch (\Throwable $e) {
            report($e);
            throw ValidationException::withMessages(['fail' => 'Teks tidak dapat diekstrak daripada fail ini: '.$e->getMessage()]);
        }
        if (mb_strlen($teks) < 20) {
            throw ValidationException::withMessages(['fail' => 'Tiada teks ditemui dalam fail (kemungkinan PDF imbasan). Sila masukkan seksyen secara manual.']);
        }

        if ($rujukan->laluan_fail) {
            Storage::disk('local')->delete($rujukan->laluan_fail);
        }
        $laluan = $fail->store('rujukan', 'local');
        $rujukan->update(['nama_fail' => $fail->getClientOriginalName(), 'laluan_fail' => $laluan]);

        if ($gantikan) {
            $rujukan->seksyen()->delete();
        }
        $mula = (int) $rujukan->seksyen()->max('susunan');
        $seksyen = $this->pemproses->pecah($teks);
        foreach ($seksyen as $i => $s) {
            $rujukan->seksyen()->create($s + ['susunan' => $mula + $i + 1]);
        }

        return count($seksyen);
    }

    private function bolehUrus(): bool
    {
        return auth()->user()->ada(Peranan::KetuaUnit, Peranan::Pentadbir);
    }
}
