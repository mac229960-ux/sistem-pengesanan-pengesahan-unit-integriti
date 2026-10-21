<x-layouts.app tajuk="Kelulusan Cadangan Tindakan">
    <div class="card mb-6 overflow-hidden">
        <div class="card-header"><div class="card-title">Menunggu Kelulusan ({{ $senarai->count() }})</div></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Rujukan</th><th>Tajuk</th><th>Pegawai Siasatan</th><th>Keputusan Siasatan</th><th>Tindakan Dicadangkan</th><th>SLA</th><th></th></tr></thead>
                <tbody>
                @forelse ($senarai as $a)
                    <tr>
                        <td class="whitespace-nowrap font-mono text-xs font-semibold">{{ $a->no_rujukan }}</td>
                        <td class="min-w-[16rem]">{{ \Illuminate\Support\Str::limit($a->tajuk, 80) }}<div class="text-xs text-slate-500">{{ $a->kategori->nama }}</div></td>
                        <td>{{ $a->pegawaiSiasatan?->name }}</td>
                        <td>{{ $a->cadanganTerkini?->keputusan_siasatan?->label() }}</td>
                        <td class="text-xs">{{ $a->cadanganTerkini?->jenisTindakan->pluck('nama')->implode('; ') }}</td>
                        <td class="whitespace-nowrap"><x-sla-badge :aduan="$a" /></td>
                        <td><a href="{{ route('aduan.show', [$a, 'tab' => 'cadangan']) }}" class="btn-primary btn-sm">Semak</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-slate-500">Tiada cadangan menunggu kelulusan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div class="card-header"><div class="card-title">Keputusan Terkini</div></div>
        <table class="table">
            <thead><tr><th>Tarikh</th><th>No. Rujukan</th><th>Keputusan</th><th>Ulasan</th><th>Oleh</th></tr></thead>
            <tbody>
            @forelse ($terkini as $k)
                <tr>
                    <td class="whitespace-nowrap">{{ $k->created_at->format('d/m/Y H:i') }}</td>
                    <td class="font-mono text-xs"><a class="text-mbsp-700 hover:underline" href="{{ route('aduan.show', [$k->aduan_id, 'tab' => 'cadangan']) }}">{{ $k->cadangan->aduan->no_rujukan }}</a></td>
                    <td><span @class(['badge', 'bg-emerald-100 text-emerald-800' => $k->keputusan === 'lulus', 'bg-orange-100 text-orange-800' => $k->keputusan !== 'lulus'])>{{ $k->keputusan === 'lulus' ? 'Diluluskan' : 'Dikembalikan' }}</span></td>
                    <td>{{ $k->ulasan }}</td>
                    <td>{{ $k->pelulus->name }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-6 text-center text-slate-500">Tiada rekod.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.app>
