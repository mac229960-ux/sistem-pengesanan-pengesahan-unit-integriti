@php $bolehUbahPihak = auth()->user()->can('siasat', $aduan) || auth()->user()->can('update', $aduan); @endphp
<div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header"><div class="card-title">Butiran Aduan</div></div>
        <div class="card-body space-y-4 text-sm">
            <div>
                <div class="text-xs font-semibold uppercase text-slate-500">Keterangan</div>
                <div class="mt-1 whitespace-pre-line text-slate-800">{{ $aduan->keterangan }}</div>
            </div>
            <dl class="grid gap-3 sm:grid-cols-2">
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Lokasi</dt><dd>{{ $aduan->lokasi }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Jabatan Terlibat</dt><dd>{{ $aduan->jabatanTerlibat?->nama ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Tarikh Kejadian</dt><dd>{{ $aduan->tarikh_kejadian?->format('d/m/Y') ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">No. Rujukan Saluran</dt><dd>{{ $aduan->no_rujukan_saluran ?? '—' }}</dd></div>
                <div><dt class="text-xs font-semibold uppercase text-slate-500">Didaftarkan Oleh</dt><dd>{{ $aduan->pencipta->name }} · {{ $aduan->created_at->format('d/m/Y H:i') }}</dd></div>
                @if ($aduan->keputusan_siasatan)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Keputusan Siasatan</dt><dd class="font-semibold">{{ $aduan->keputusan_siasatan->label() }}</dd></div>
                @endif
                @if ($aduan->sebab_tidak_diteruskan)
                    <div class="sm:col-span-2"><dt class="text-xs font-semibold uppercase text-slate-500">Sebab Tidak Diteruskan</dt><dd>{{ $aduan->sebab_tidak_diteruskan }}</dd></div>
                @endif
                @if ($aduan->retensi_hingga)
                    <div><dt class="text-xs font-semibold uppercase text-slate-500">Simpan Rekod Hingga</dt><dd>{{ $aduan->retensi_hingga->format('d/m/Y') }}</dd></div>
                @endif
            </dl>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><div class="card-title">Pengadu</div><span class="text-xs text-slate-400">🔒 Sulit</span></div>
        <div class="card-body text-sm">
            @if ($aduan->tanpa_nama)
                <p class="text-slate-500">Aduan tanpa nama.</p>
            @else
                <dl class="space-y-2">
                    <div><dt class="text-xs text-slate-500">Nama</dt><dd class="font-medium">{{ $aduan->nama_pengadu }}</dd></div>
                    <div><dt class="text-xs text-slate-500">No. KP</dt><dd>{{ $aduan->no_kp_pengadu ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Telefon</dt><dd>{{ $aduan->telefon_pengadu ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">E-mel</dt><dd>{{ $aduan->emel_pengadu ?: '—' }}</dd></div>
                    <div><dt class="text-xs text-slate-500">Alamat</dt><dd>{{ $aduan->alamat_pengadu ?: '—' }}</dd></div>
                </dl>
            @endif
        </div>
    </div>

    <div class="card lg:col-span-3" x-data="{ tambahPihak: false, borang: {} , tambah(p) { this.borang = { pegawai_hr_id: p.id ?? '', nama: p.nama ?? '', no_pekerja: p.no_pekerja ?? '', jawatan: p.jawatan ? p.jawatan + (p.gred ? ' (' + p.gred + ')' : '') : '', jabatan: p.jabatan ?? '' }; this.tambahPihak = true; } }">
        <div class="card-header">
            <div class="card-title">Pihak Terlibat</div>
            @if ($bolehUbahPihak)<button type="button" @click="tambahPihak = !tambahPihak; borang = {}" class="btn-secondary btn-sm">+ Tambah</button>@endif
        </div>
        @if ($bolehUbahPihak)
            <div x-show="tambahPihak" x-cloak class="space-y-3 border-b border-slate-200 bg-slate-50 p-4">
                @include('aduan.partials.carian-hr')
                <form method="POST" action="{{ route('pihak.store', $aduan) }}" class="grid gap-2 md:grid-cols-6">
                    @csrf
                    <input type="hidden" name="pegawai_hr_id" :value="borang.pegawai_hr_id">
                    <input name="nama" x-model="borang.nama" class="input md:col-span-2" placeholder="Nama" required>
                    <input name="no_pekerja" x-model="borang.no_pekerja" class="input" placeholder="No. pekerja">
                    <input name="jawatan" x-model="borang.jawatan" class="input" placeholder="Jawatan">
                    <input name="jabatan" x-model="borang.jabatan" class="input" placeholder="Jabatan">
                    <select name="peranan" class="input">
                        @foreach (\App\Models\AduanPihakTerlibat::PERANAN as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                    <input name="catatan" class="input md:col-span-5" placeholder="Catatan (pilihan)">
                    <button class="btn-primary btn-sm">Simpan</button>
                </form>
            </div>
        @endif
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>No. Pekerja</th><th>Jawatan</th><th>Jabatan</th><th>Peranan</th><th>Catatan</th>@if($bolehUbahPihak)<th></th>@endif</tr></thead>
                <tbody>
                @forelse ($aduan->pihakTerlibat as $p)
                    <tr>
                        <td class="font-medium">{{ $p->nama }} @if($p->pegawai_hr_id)<span class="badge bg-sky-50 text-sky-700" title="Dipadankan dengan rekod HR">HR</span>@endif</td>
                        <td>{{ $p->no_pekerja ?? '—' }}</td>
                        <td>{{ $p->jawatan ?? '—' }}</td>
                        <td>{{ $p->jabatan ?? '—' }}</td>
                        <td>{{ \App\Models\AduanPihakTerlibat::PERANAN[$p->peranan] ?? $p->peranan }}</td>
                        <td>{{ $p->catatan }}</td>
                        @if ($bolehUbahPihak)
                            <td class="text-right">
                                <form method="POST" action="{{ route('pihak.destroy', [$aduan, $p]) }}" onsubmit="return confirm('Hapus pihak ini?')">@csrf @method('DELETE')
                                    <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                </form>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-6 text-center text-slate-500">Tiada pihak terlibat direkodkan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
