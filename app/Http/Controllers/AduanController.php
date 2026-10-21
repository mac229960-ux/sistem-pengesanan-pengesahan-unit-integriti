<?php

namespace App\Http\Controllers;

use App\Enums\Peranan;
use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\AduanPihakTerlibat;
use App\Models\Jabatan;
use App\Models\JenisTindakan;
use App\Models\KategoriAduan;
use App\Models\SaluranAduan;
use App\Models\StatusSejarah;
use App\Models\User;
use App\Services\AliranKerja;
use App\Services\Audit;
use App\Services\Notifikasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AduanController extends Controller
{
    public function index(Request $request)
    {
        $this->authorize('viewAny', Aduan::class);
        $user = $request->user();

        $q = Aduan::with(['kategori', 'saluran', 'pegawaiSiasatan', 'jabatanTerlibat'])->bolehDilihat($user);

        if ($cari = trim((string) $request->input('cari'))) {
            $q->where(fn ($w) => $w->where('no_rujukan', 'like', "%{$cari}%")
                ->orWhere('tajuk', 'like', "%{$cari}%")
                ->orWhere('lokasi', 'like', "%{$cari}%")
                ->orWhereHas('pihakTerlibat', fn ($p) => $p->where('nama', 'like', "%{$cari}%")->orWhere('no_pekerja', 'like', "%{$cari}%")));
        }
        if ($s = $request->input('status')) {
            $s === 'aktif' ? $q->aktif() : $q->where('status', $s);
        }
        $q->when($request->input('kategori'), fn ($w, $v) => $w->where('kategori_id', $v))
            ->when($request->input('pegawai'), fn ($w, $v) => $w->where('pegawai_siasatan_id', $v))
            ->when($request->input('keutamaan'), fn ($w, $v) => $w->where('keutamaan', $v));

        match ($request->input('sla')) {
            'lewat' => $q->lewat(),
            'hampir' => $q->aktif()->whereBetween('tarikh_akhir_sla', [today(), today()->addDays(30)]),
            default => null,
        };

        $susun = $request->input('susun', 'terkini');
        match ($susun) {
            'sla' => $q->orderByRaw("CASE WHEN status IN ('selesai','tidak_diteruskan') THEN 1 ELSE 0 END")->orderBy('tarikh_akhir_sla'),
            default => $q->latest('tarikh_terima')->latest('id'),
        };

        return view('aduan.index', [
            'senarai' => $q->paginate(20)->withQueryString(),
            'kategori' => KategoriAduan::orderBy('nama')->get(),
            'pegawai' => User::peranan(Peranan::PegawaiSiasatan)->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        $this->authorize('create', Aduan::class);

        return view('aduan.borang', $this->dataBorang() + ['aduan' => new Aduan(['tarikh_terima' => today(), 'keutamaan' => 'sederhana'])]);
    }

    public function store(Request $request)
    {
        $this->authorize('create', Aduan::class);
        $data = $this->sahkan($request);

        $aduan = DB::transaction(function () use ($data, $request) {
            $terima = Carbon::parse($data['tarikh_terima']);
            $aduan = Aduan::create($data + [
                'no_rujukan' => Aduan::janaNoRujukan($terima),
                'status' => StatusAduan::Baru,
                'tarikh_akhir_sla' => $terima->copy()->addMonths(config('integriti.sla_bulan')),
                'dicipta_oleh' => $request->user()->id,
            ]);

            foreach ($request->input('pihak', []) as $p) {
                if (! empty($p['nama'])) {
                    $aduan->pihakTerlibat()->create([
                        'pegawai_hr_id' => $p['pegawai_hr_id'] ?? null, 'nama' => $p['nama'], 'no_pekerja' => $p['no_pekerja'] ?? null,
                        'jawatan' => $p['jawatan'] ?? null, 'jabatan' => $p['jabatan'] ?? null, 'peranan' => $p['peranan'] ?? 'didakwa',
                    ]);
                }
            }

            StatusSejarah::create(['aduan_id' => $aduan->id, 'status_ke' => StatusAduan::Baru->value, 'user_id' => $request->user()->id, 'catatan' => 'Aduan didaftarkan']);
            Audit::log('aduan.daftar', $aduan, 'Daftar aduan '.$aduan->no_rujukan, baru: $aduan->only(['no_rujukan', 'kategori_id', 'saluran_id', 'tajuk', 'lokasi', 'tarikh_terima']));

            return $aduan;
        });

        Notifikasi::hantar(Notifikasi::ketuaUnit(), $aduan, 'aduan_baru', 'Aduan baru diterima',
            "Aduan baru {$aduan->no_rujukan} ({$aduan->kategori->nama}) telah didaftarkan dan menunggu agihan.");

        return redirect()->route('aduan.show', $aduan)->with('berjaya', "Aduan {$aduan->no_rujukan} berjaya didaftarkan. Tarikh akhir SLA: {$aduan->tarikh_akhir_sla->format('d/m/Y')}.");
    }

    public function show(Request $request, Aduan $aduan)
    {
        $this->authorize('view', $aduan);
        $user = $request->user();

        $aduan->load(['kategori', 'saluran', 'jabatanTerlibat', 'pegawaiSiasatan', 'pencipta', 'pihakTerlibat',
            'sejarahStatus.pengguna', 'tindakanDiambil.jenisTindakan', 'tindakanDiambil.perekod']);

        $siasatan = $user->can('lihatSiasatan', $aduan);
        if ($siasatan) {
            $aduan->load(['aktiviti.pegawai', 'bukti.pemuatnaik', 'bukti.pengesah', 'aiCadangan.pengguna', 'aiCadangan.penyemak',
                'cadanganTindakan.penyedia', 'cadanganTindakan.jenisTindakan', 'kelulusan.pelulus']);
        }

        Audit::log('aduan.lihat', $aduan, 'Lihat kes '.$aduan->no_rujukan);

        return view('aduan.show', [
            'aduan' => $aduan,
            'siasatan' => $siasatan,
            'pegawai' => User::aktif()->peranan(Peranan::PegawaiSiasatan)->withCount(['aduanDisiasat as beban' => fn ($q) => $q->aktif()])->orderBy('name')->get(),
            'jenisTindakan' => JenisTindakan::where('aktif', true)->orderBy('susunan')->get(),
            'tab' => $request->input('tab', 'maklumat'),
        ]);
    }

    public function edit(Aduan $aduan)
    {
        $this->authorize('update', $aduan);

        return view('aduan.borang', $this->dataBorang() + ['aduan' => $aduan]);
    }

    public function update(Request $request, Aduan $aduan)
    {
        $this->authorize('update', $aduan);
        $data = $this->sahkan($request, $aduan);

        // Tarikh terima menentukan SLA; kemas kini tarikh akhir jika berubah.
        $data['tarikh_akhir_sla'] = Carbon::parse($data['tarikh_terima'])->addMonths(config('integriti.sla_bulan'));
        $aduan->update($data);
        Audit::perubahan('aduan.kemaskini', $aduan, 'Kemas kini maklumat aduan');

        return redirect()->route('aduan.show', $aduan)->with('berjaya', 'Maklumat aduan dikemas kini.');
    }

    public function agih(Request $request, Aduan $aduan)
    {
        $this->authorize('agih', $aduan);
        $data = $request->validate([
            'pegawai_siasatan_id' => ['required', Rule::exists('users', 'id')->where('peranan', Peranan::PegawaiSiasatan->value)->where('aktif', true)],
            'catatan' => ['nullable', 'string', 'max:1000'],
        ]);
        $pegawai = User::findOrFail($data['pegawai_siasatan_id']);
        $lama = $aduan->pegawaiSiasatan;

        DB::transaction(function () use ($aduan, $pegawai, $data, $request) {
            $tambahan = ['pegawai_siasatan_id' => $pegawai->id, 'diagih_oleh' => $request->user()->id, 'tarikh_agihan' => now()];
            $catatan = 'Diagihkan kepada '.$pegawai->name.(! empty($data['catatan']) ? ': '.$data['catatan'] : '');
            // Agihan semula semasa siasatan tidak menukar status kes.
            if (in_array($aduan->status, [StatusAduan::Baru, StatusAduan::Diagihkan], true)) {
                AliranKerja::tukar($aduan, StatusAduan::Diagihkan, $catatan, $tambahan);
            } else {
                $aduan->update($tambahan);
                Audit::log('aduan.agih_semula', $aduan, $catatan);
            }
        });

        Notifikasi::hantar($pegawai, $aduan, 'agihan', 'Kes baharu diagihkan kepada anda',
            "Kes {$aduan->no_rujukan} telah diagihkan kepada anda untuk siasatan. Tarikh akhir SLA: {$aduan->tarikh_akhir_sla->format('d/m/Y')}.");
        if ($lama && $lama->id !== $pegawai->id) {
            Notifikasi::hantar($lama, $aduan, 'agihan', 'Kes diagihkan semula', "Kes {$aduan->no_rujukan} telah diagihkan semula kepada pegawai lain.");
        }

        return back()->with('berjaya', "Kes diagihkan kepada {$pegawai->name}.");
    }

    public function mulaSiasatan(Request $request, Aduan $aduan)
    {
        abort_unless($request->user()->can('siasat', $aduan) && $aduan->status === StatusAduan::Diagihkan, 403);
        AliranKerja::tukar($aduan, StatusAduan::DalamSiasatan, 'Siasatan dimulakan');

        return back()->with('berjaya', 'Status kes ditukar kepada Dalam Siasatan.');
    }

    public function tidakDiteruskan(Request $request, Aduan $aduan)
    {
        $this->authorize('tidakDiteruskan', $aduan);
        $data = $request->validate(['sebab' => ['required', 'string', 'min:10', 'max:2000']]);
        AliranKerja::tukar($aduan, StatusAduan::TidakDiteruskan, $data['sebab'], ['sebab_tidak_diteruskan' => $data['sebab']]);

        return back()->with('berjaya', 'Aduan ditandakan Tidak Diteruskan.');
    }

    public function cetak(Request $request, Aduan $aduan)
    {
        $this->authorize('lihatSiasatan', $aduan);
        $aduan->load(['kategori', 'saluran', 'jabatanTerlibat', 'pegawaiSiasatan', 'pihakTerlibat', 'aktiviti.pegawai', 'bukti',
            'cadanganTindakan.jenisTindakan', 'cadanganTindakan.penyedia', 'kelulusan.pelulus', 'tindakanDiambil.jenisTindakan', 'sejarahStatus.pengguna']);
        Audit::log('aduan.cetak', $aduan, 'Cetak ringkasan kes');

        return Pdf::loadView('aduan.cetak', ['aduan' => $aduan, 'pencetak' => $request->user()])
            ->setPaper('a4')->stream(str_replace('/', '-', $aduan->no_rujukan).'.pdf');
    }

    private function dataBorang(): array
    {
        return [
            'kategori' => KategoriAduan::where('aktif', true)->orderBy('nama')->get(),
            'saluran' => SaluranAduan::where('aktif', true)->orderBy('nama')->get(),
            'jabatan' => Jabatan::where('aktif', true)->orderBy('nama')->get(),
        ];
    }

    private function sahkan(Request $request, ?Aduan $aduan = null): array
    {
        $data = $request->validate([
            'saluran_id' => ['required', 'exists:saluran_aduan,id'],
            'kategori_id' => ['required', 'exists:kategori_aduan,id'],
            'no_rujukan_saluran' => ['nullable', 'string', 'max:100'],
            'tarikh_terima' => ['required', 'date', 'before_or_equal:today'],
            'tarikh_kejadian' => ['nullable', 'date', 'before_or_equal:tarikh_terima'],
            'tanpa_nama' => ['boolean'],
            'nama_pengadu' => ['nullable', 'required_unless:tanpa_nama,1', 'string', 'max:200'],
            'no_kp_pengadu' => ['nullable', 'string', 'max:20'],
            'telefon_pengadu' => ['nullable', 'string', 'max:30'],
            'emel_pengadu' => ['nullable', 'email', 'max:150'],
            'alamat_pengadu' => ['nullable', 'string', 'max:500'],
            'tajuk' => ['required', 'string', 'max:255'],
            'keterangan' => ['required', 'string', 'min:20'],
            'lokasi' => ['required', 'string', 'max:255'],
            'jabatan_terlibat_id' => ['nullable', 'exists:jabatan,id'],
            'keutamaan' => ['required', Rule::in(array_keys(Aduan::KEUTAMAAN))],
            'pihak' => [$aduan ? 'nullable' : 'required', 'array'],
            'pihak.*.nama' => ['nullable', 'string', 'max:200'],
            'pihak.*.peranan' => ['nullable', Rule::in(array_keys(AduanPihakTerlibat::PERANAN))],
        ], [
            'nama_pengadu.required_unless' => 'Nama pengadu wajib diisi kecuali aduan tanpa nama.',
            'keterangan.min' => 'Keterangan aduan terlalu ringkas (sekurang-kurangnya 20 aksara).',
        ], [
            'saluran_id' => 'saluran', 'kategori_id' => 'kategori', 'tarikh_terima' => 'tarikh terima',
            'tarikh_kejadian' => 'tarikh kejadian', 'keterangan' => 'keterangan aduan',
        ]);

        // BRS-015: pihak terlibat wajib bagi aduan baru.
        if (! $aduan && ! collect($request->input('pihak', []))->filter(fn ($p) => ! empty($p['nama']))->count()) {
            throw ValidationException::withMessages(['pihak' => 'Sekurang-kurangnya seorang pihak terlibat perlu dinyatakan.']);
        }

        $data['tanpa_nama'] = $request->boolean('tanpa_nama');
        if ($data['tanpa_nama']) {
            foreach (['nama_pengadu', 'no_kp_pengadu', 'telefon_pengadu', 'emel_pengadu', 'alamat_pengadu'] as $m) {
                $data[$m] = null;
            }
        }
        unset($data['pihak']);

        return $data;
    }
}
