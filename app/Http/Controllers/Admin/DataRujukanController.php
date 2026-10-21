<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Models\JenisTindakan;
use App\Models\KategoriAduan;
use App\Models\SaluranAduan;
use App\Services\Audit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Senarai rujukan (kategori, saluran, jabatan, jenis tindakan). Tiada hapus — hanya
 * nyahaktif, kerana rekod dirujuk oleh kes sedia ada.
 */
class DataRujukanController extends Controller
{
    private const JENIS = [
        'kategori' => [KategoriAduan::class, 'Kategori Aduan'],
        'saluran' => [SaluranAduan::class, 'Saluran Aduan'],
        'jabatan' => [Jabatan::class, 'Jabatan'],
        'tindakan' => [JenisTindakan::class, 'Jenis Tindakan'],
    ];

    public function index(string $jenis)
    {
        [$kelas, $label] = $this->jenis($jenis);
        $q = $kelas::query();
        $jenis === 'tindakan' ? $q->orderBy('susunan')->orderBy('nama') : $q->orderBy('kod');

        return view('admin.data.index', [
            'jenis' => $jenis,
            'label' => $label,
            'rekod' => $q->get(),
            'adaKeterangan' => in_array($jenis, ['kategori', 'tindakan'], true),
        ]);
    }

    public function store(Request $request, string $jenis)
    {
        [$kelas, $label] = $this->jenis($jenis);
        $rekod = $kelas::create($this->sahkan($request, $jenis));
        Audit::log("data.{$jenis}.cipta", $rekod, "Tambah {$label}: {$rekod->nama}", baru: $rekod->toArray());

        return back()->with('berjaya', "{$label} berjaya ditambah.");
    }

    public function update(Request $request, string $jenis, int $id)
    {
        [$kelas, $label] = $this->jenis($jenis);
        $rekod = $kelas::findOrFail($id);
        $rekod->fill($this->sahkan($request, $jenis, $rekod->id))->save();
        Audit::perubahan("data.{$jenis}.kemaskini", $rekod, "Kemas kini {$label}: {$rekod->nama}");

        return back()->with('berjaya', "{$label} berjaya dikemas kini.");
    }

    private function jenis(string $jenis): array
    {
        abort_unless(isset(self::JENIS[$jenis]), 404);

        return self::JENIS[$jenis];
    }

    private function sahkan(Request $request, string $jenis, ?int $abaiId = null): array
    {
        $kelas = self::JENIS[$jenis][0];
        $jadual = (new $kelas)->getTable();

        $peraturan = [
            'kod' => ['required', 'string', 'max:'.($jenis === 'tindakan' ? 30 : 20), 'regex:/^[A-Za-z0-9\-_]+$/', Rule::unique($jadual, 'kod')->ignore($abaiId)],
            'nama' => ['required', 'string', 'max:255'],
        ];
        if (in_array($jenis, ['kategori', 'tindakan'], true)) {
            $peraturan['keterangan'] = ['nullable', 'string', 'max:2000'];
        }
        if ($jenis === 'tindakan') {
            $peraturan += [
                'kumpulan' => ['required', Rule::in(array_keys(JenisTindakan::KUMPULAN))],
                'rujukan_peraturan' => ['nullable', 'string', 'max:255'],
                'susunan' => ['nullable', 'integer', 'min:0', 'max:65535'],
            ];
        }

        $data = $request->validate($peraturan);
        $data['kod'] = strtoupper($data['kod']);
        $data['aktif'] = $request->boolean('aktif');
        if ($jenis === 'tindakan') {
            $data['susunan'] = (int) ($data['susunan'] ?? 0);
        }

        return $data;
    }
}
