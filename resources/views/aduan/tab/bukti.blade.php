@php $boleh = auth()->user()->can('siasat', $aduan); @endphp
<div class="grid gap-4 lg:grid-cols-3">
    <div class="card lg:col-span-2">
        <div class="card-header">
            <div class="card-title">Senarai Bukti / Bahan Kes</div>
            <div class="text-xs text-slate-500">{{ $aduan->bukti->where('status_pengesahan', 'disahkan')->count() }} / {{ $aduan->bukti->count() }} disahkan</div>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse ($aduan->bukti as $b)
                <div class="p-4" x-data="{ sahkan: false }">
                    <div class="flex flex-wrap items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="font-mono text-xs font-semibold text-mbsp-700">{{ $b->no_bukti }}</span>
                                <span class="font-medium">{{ $b->tajuk }}</span>
                                <span @class(['badge', 'bg-emerald-100 text-emerald-800' => $b->status_pengesahan === 'disahkan', 'bg-red-100 text-red-800' => $b->status_pengesahan === 'ditolak', 'bg-amber-100 text-amber-800' => $b->status_pengesahan === 'belum_disahkan'])>{{ \App\Models\Bukti::STATUS[$b->status_pengesahan] }}</span>
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500">
                                {{ \App\Models\Bukti::JENIS[$b->jenis] }} · Sumber: {{ $b->sumber ?? '—' }} · Diperoleh: {{ $b->tarikh_diperoleh?->format('d/m/Y') ?? '—' }} · Oleh {{ $b->pemuatnaik->name }}
                            </div>
                            @if ($b->keterangan)<p class="mt-1 text-sm text-slate-700">{{ $b->keterangan }}</p>@endif
                            @if ($b->laluan_fail)
                                <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                    <a href="{{ route('bukti.muat-turun', [$aduan, $b]) }}" class="btn-secondary btn-sm">⬇ {{ \Illuminate\Support\Str::limit($b->nama_fail_asal, 40) }} ({{ $b->saizPaparan() }})</a>
                                    <span class="font-mono text-slate-400" title="Cincang SHA-256 untuk pengesahan integriti fail">SHA-256: {{ substr($b->sha256, 0, 16) }}…</span>
                                </div>
                            @else
                                <div class="mt-1 text-xs text-slate-400">Tiada fail digital (bukti fizikal / rekod sahaja)</div>
                            @endif
                            @if ($b->pengesah)<div class="mt-1 text-xs text-slate-500">Pengesahan oleh {{ $b->pengesah->name }} pada {{ $b->tarikh_disahkan?->format('d/m/Y') }}{{ $b->catatan_pengesahan ? ': '.$b->catatan_pengesahan : '' }}</div>@endif
                        </div>
                        @if ($boleh)
                            <div class="flex gap-2">
                                <button type="button" @click="sahkan=!sahkan" class="btn-secondary btn-sm">Pengesahan</button>
                                @if (auth()->user()->isKetuaUnit() || $b->dimuatnaik_oleh === auth()->id())
                                    <form method="POST" action="{{ route('bukti.destroy', [$aduan, $b]) }}" onsubmit="return confirm('Keluarkan bukti ini? Rekod audit akan dikekalkan.')">@csrf @method('DELETE')
                                        <button class="btn-secondary btn-sm text-red-600">Keluarkan</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>
                    @if ($boleh)
                        <form x-show="sahkan" x-cloak method="POST" action="{{ route('bukti.sahkan', [$aduan, $b]) }}" class="mt-3 grid gap-2 rounded-md bg-slate-50 p-3 md:grid-cols-4">
                            @csrf
                            <select name="status_pengesahan" class="input">
                                @foreach (\App\Models\Bukti::STATUS as $v => $l)<option value="{{ $v }}" @selected($b->status_pengesahan === $v)>{{ $l }}</option>@endforeach
                            </select>
                            <input name="catatan_pengesahan" value="{{ $b->catatan_pengesahan }}" class="input md:col-span-2" placeholder="Catatan pengesahan">
                            <button class="btn-primary btn-sm">Simpan</button>
                        </form>
                    @endif
                </div>
            @empty
                <div class="p-10 text-center text-sm text-slate-500">Belum ada bukti dikumpul.</div>
            @endforelse
        </div>
    </div>

    @if ($boleh)
        <div class="card h-fit">
            <div class="card-header"><div class="card-title">Tambah Bukti</div></div>
            <form method="POST" action="{{ route('bukti.store', $aduan) }}" enctype="multipart/form-data" class="card-body space-y-3">
                @csrf
                <x-medan label="Jenis Bukti" nama="jenis" wajib>
                    <select name="jenis" id="jenis" class="input">
                        @foreach (\App\Models\Bukti::JENIS as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                    </select>
                </x-medan>
                <x-medan label="Tajuk" nama="tajuk" wajib><input id="tajuk" name="tajuk" value="{{ old('tajuk') }}" class="input" required></x-medan>
                <x-medan label="Keterangan" nama="keterangan"><textarea id="keterangan" name="keterangan" rows="3" class="input">{{ old('keterangan') }}</textarea></x-medan>
                <x-medan label="Sumber" nama="sumber"><input id="sumber" name="sumber" value="{{ old('sumber') }}" class="input" placeholder="Cth: Jabatan Kewangan / saksi"></x-medan>
                <x-medan label="Tarikh Diperoleh" nama="tarikh_diperoleh"><input id="tarikh_diperoleh" type="date" name="tarikh_diperoleh" value="{{ old('tarikh_diperoleh', today()->toDateString()) }}" class="input"></x-medan>
                <x-medan label="Fail (pilihan)" nama="fail" nota="Maks. {{ config('integriti.bukti.saiz_maks_kb') / 1024 }} MB. Fail disulitkan dan dicap SHA-256.">
                    <input id="fail" type="file" name="fail" class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-mbsp-50 file:px-3 file:py-2 file:text-mbsp-700">
                </x-medan>
                <button class="btn-primary w-full">Simpan Bukti</button>
            </form>
        </div>
    @endif
</div>
