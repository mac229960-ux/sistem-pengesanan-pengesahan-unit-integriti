<x-layouts.app :tajuk="$rujukan->kod">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-2">
        <a href="{{ route('rujukan.index') }}" class="text-sm text-slate-500 hover:underline">← Pustaka Peraturan</a>
        @if ($bolehUrus)
            <div class="flex gap-2">
                <a href="{{ route('rujukan.edit', $rujukan) }}" class="btn-secondary btn-sm">Kemas Kini / Muat Naik</a>
                <form method="POST" action="{{ route('rujukan.destroy', $rujukan) }}" onsubmit="return confirm('Hapus rujukan ini beserta semua seksyennya?')">
                    @csrf @method('DELETE')
                    <button class="btn-danger btn-sm">Hapus</button>
                </form>
            </div>
        @endif
    </div>

    <div class="card mb-6">
        <div class="card-body">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="text-lg font-semibold text-slate-800">{{ $rujukan->tajuk }}</h2>
                    <div class="mt-1 text-sm text-slate-500">
                        {{ \App\Models\RujukanPeraturan::JENIS[$rujukan->jenis] ?? $rujukan->jenis }}
                        @if ($rujukan->penerbit) · {{ $rujukan->penerbit }} @endif
                        @if ($rujukan->tahun) · {{ $rujukan->tahun }} @endif
                    </div>
                </div>
                <div class="flex gap-1">
                    @if ($rujukan->teks_rasmi)
                        <span class="badge bg-emerald-100 text-emerald-800">Teks Rasmi</span>
                    @else
                        <span class="badge bg-amber-100 text-amber-800">Ringkasan – belum disahkan</span>
                    @endif
                    <span class="badge {{ $rujukan->aktif ? 'bg-sky-100 text-sky-800' : 'bg-slate-200 text-slate-600' }}">{{ $rujukan->aktif ? 'Aktif' : 'Tidak aktif' }}</span>
                </div>
            </div>
            @if ($rujukan->ringkasan)<p class="mt-3 text-sm text-slate-700">{{ $rujukan->ringkasan }}</p>@endif
            <div class="mt-3 flex flex-wrap gap-x-6 gap-y-1 text-xs text-slate-500">
                @if ($rujukan->sumber_url)<span>Sumber: <a href="{{ $rujukan->sumber_url }}" target="_blank" rel="noopener" class="text-mbsp-700 hover:underline">{{ $rujukan->sumber_url }}</a></span>@endif
                @if ($rujukan->nama_fail)<span>Fail: {{ $rujukan->nama_fail }}</span>@endif
                <span>Dikemas kini: {{ $rujukan->updated_at?->format('d/m/Y H:i') }}</span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Seksyen ({{ $rujukan->seksyen->count() }})</h3></div>
        <div class="divide-y divide-slate-100">
            @forelse ($rujukan->seksyen as $s)
                <div x-data="{ edit: false, buka: false }" class="px-5 py-4">
                    <div x-show="!edit">
                        <div class="flex items-start justify-between gap-3">
                            <button type="button" @click="buka=!buka" class="text-left">
                                <span class="font-semibold text-slate-800">{{ $s->no_seksyen ? $s->no_seksyen.' — ' : '' }}{{ $s->tajuk ?? 'Tanpa tajuk' }}</span>
                                <span class="ml-1 text-xs text-slate-400" x-text="buka ? '▲' : '▼'"></span>
                            </button>
                            @if ($bolehUrus)
                                <div class="flex shrink-0 gap-2">
                                    <button type="button" @click="edit=true" class="text-xs text-mbsp-700 hover:underline">Ubah</button>
                                    <form method="POST" action="{{ route('rujukan.seksyen.destroy', [$rujukan, $s]) }}" onsubmit="return confirm('Hapus seksyen ini?')">
                                        @csrf @method('DELETE')
                                        <button class="text-xs text-red-600 hover:underline">Hapus</button>
                                    </form>
                                </div>
                            @endif
                        </div>
                        <div class="mt-2 whitespace-pre-line text-sm text-slate-700" :class="buka ? '' : 'line-clamp-3'">{{ $s->kandungan }}</div>
                    </div>
                    @if ($bolehUrus)
                        <form x-show="edit" x-cloak method="POST" action="{{ route('rujukan.seksyen.update', [$rujukan, $s]) }}" class="grid gap-3 sm:grid-cols-4">
                            @csrf @method('PUT')
                            <input name="no_seksyen" value="{{ $s->no_seksyen }}" placeholder="No. seksyen" class="input">
                            <input name="tajuk" value="{{ $s->tajuk }}" placeholder="Tajuk" class="input sm:col-span-2">
                            <input name="susunan" type="number" min="0" value="{{ $s->susunan }}" placeholder="Susunan" class="input">
                            <textarea name="kandungan" rows="8" required class="input sm:col-span-4">{{ $s->kandungan }}</textarea>
                            <div class="flex justify-end gap-2 sm:col-span-4">
                                <button type="button" @click="edit=false" class="btn-secondary btn-sm">Batal</button>
                                <button class="btn-primary btn-sm">Simpan</button>
                            </div>
                        </form>
                    @endif
                </div>
            @empty
                <div class="px-5 py-8 text-center text-sm text-slate-500">Belum ada seksyen. Muat naik fail atau tambah seksyen secara manual.</div>
            @endforelse
        </div>
        @if ($bolehUrus)
            <form method="POST" action="{{ route('rujukan.seksyen.store', $rujukan) }}" class="grid gap-3 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:grid-cols-4">
                @csrf
                <div class="text-sm font-semibold text-slate-700 sm:col-span-4">Tambah Seksyen</div>
                <input name="no_seksyen" value="{{ old('no_seksyen') }}" placeholder="No. seksyen (cth. Peraturan 4)" class="input">
                <input name="tajuk" value="{{ old('tajuk') }}" placeholder="Tajuk" class="input sm:col-span-2">
                <input name="susunan" type="number" min="0" value="{{ old('susunan') }}" placeholder="Susunan (pilihan)" class="input">
                <textarea name="kandungan" rows="5" required placeholder="Kandungan seksyen…" class="input sm:col-span-4">{{ old('kandungan') }}</textarea>
                <div class="flex justify-end sm:col-span-4"><button class="btn-primary btn-sm">Tambah</button></div>
            </form>
        @endif
    </div>
</x-layouts.app>
