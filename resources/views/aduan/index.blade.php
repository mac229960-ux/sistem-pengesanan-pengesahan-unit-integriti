<x-layouts.app tajuk="Senarai Aduan">
    <div class="card mb-4">
        <form method="GET" class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-6">
            <div class="lg:col-span-2">
                <label class="label">Carian</label>
                <input name="cari" value="{{ request('cari') }}" class="input" placeholder="No. rujukan, tajuk, lokasi, nama pegawai…">
            </div>
            <div>
                <label class="label">Status</label>
                <select name="status" class="input">
                    <option value="">Semua</option>
                    <option value="aktif" @selected(request('status') === 'aktif')>Semua kes aktif</option>
                    @foreach (\App\Enums\StatusAduan::options() as $v => $l)
                        <option value="{{ $v }}" @selected(request('status') === $v)>{{ $l }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="label">Kategori</label>
                <select name="kategori" class="input">
                    <option value="">Semua</option>
                    @foreach ($kategori as $k)<option value="{{ $k->id }}" @selected(request('kategori') == $k->id)>{{ $k->nama }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">SLA</label>
                <select name="sla" class="input">
                    <option value="">Semua</option>
                    <option value="hampir" @selected(request('sla') === 'hampir')>Hampir tamat (≤30 hari)</option>
                    <option value="lewat" @selected(request('sla') === 'lewat')>Lewat SLA</option>
                </select>
            </div>
            @unless (auth()->user()->isPegawaiSiasatan())
                <div>
                    <label class="label">Pegawai Siasatan</label>
                    <select name="pegawai" class="input">
                        <option value="">Semua</option>
                        @foreach ($pegawai as $p)<option value="{{ $p->id }}" @selected(request('pegawai') == $p->id)>{{ $p->name }}</option>@endforeach
                    </select>
                </div>
            @endunless
            <div>
                <label class="label">Susunan</label>
                <select name="susun" class="input">
                    <option value="terkini">Terkini diterima</option>
                    <option value="sla" @selected(request('susun') === 'sla')>Tarikh akhir SLA</option>
                </select>
            </div>
            <div class="flex items-end gap-2 lg:col-span-5">
                <button class="btn-primary">Tapis</button>
                <a href="{{ route('aduan.index') }}" class="btn-secondary">Set Semula</a>
            </div>
        </form>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header">
            <div class="card-title">{{ $senarai->total() }} aduan</div>
            @can('create', \App\Models\Aduan::class)
                <a href="{{ route('aduan.create') }}" class="btn-primary btn-sm">+ Daftar Aduan</a>
            @endcan
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr>
                    <th>No. Rujukan</th><th>Tajuk / Kategori</th><th>Tarikh Terima</th><th>Pegawai Siasatan</th><th>Status</th><th>SLA</th>
                </tr></thead>
                <tbody>
                @forelse ($senarai as $a)
                    <tr>
                        <td class="whitespace-nowrap font-mono text-xs"><a href="{{ route('aduan.show', $a) }}" class="font-semibold text-mbsp-700 hover:underline">{{ $a->no_rujukan }}</a></td>
                        <td class="min-w-[18rem]">
                            <a href="{{ route('aduan.show', $a) }}" class="font-medium text-slate-800 hover:underline">{{ \Illuminate\Support\Str::limit($a->tajuk, 90) }}</a>
                            <div class="text-xs text-slate-500">{{ $a->kategori->nama }} · {{ $a->saluran->nama }}
                                @if (in_array($a->keutamaan, ['tinggi', 'kritikal']))<span class="ml-1 badge bg-red-50 text-red-700">{{ \App\Models\Aduan::KEUTAMAAN[$a->keutamaan] }}</span>@endif
                            </div>
                        </td>
                        <td class="whitespace-nowrap">{{ $a->tarikh_terima->format('d/m/Y') }}</td>
                        <td>{{ $a->pegawaiSiasatan?->name ?? '—' }}</td>
                        <td><x-status-badge :status="$a->status" /></td>
                        <td class="whitespace-nowrap"><x-sla-badge :aduan="$a" /></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">Tiada aduan ditemui.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($senarai->hasPages())<div class="border-t border-slate-200 px-4 py-3">{{ $senarai->links() }}</div>@endif
    </div>
</x-layouts.app>
