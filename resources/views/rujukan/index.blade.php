<x-layouts.app tajuk="Pustaka Peraturan">
    <div class="mb-4 rounded-lg bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-200">
        <div class="font-semibold">Tentang pustaka ini</div>
        <p class="mt-1">Cadangan AI hanya memetik peraturan, akta dan pekeliling yang terdapat dalam pustaka ini. Rujukan bertanda
            <span class="badge bg-amber-100 text-amber-800">Ringkasan – belum disahkan</span> ialah ringkasan permulaan dan perlu digantikan dengan teks rasmi.
            Dapatkan teks rasmi daripada laman web <a href="https://www.jpa.gov.my" target="_blank" rel="noopener" class="font-medium underline">JPA</a>
            dan <a href="https://www.mbsp.gov.my" target="_blank" rel="noopener" class="font-medium underline">portal MBSP</a>, kemudian muat naik fail PDF di sini.</p>
    </div>

    <div class="card">
        <div class="card-header flex-wrap">
            <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
                <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Cari tajuk, kod atau kandungan…" class="input max-w-xs">
                <select name="jenis" class="input max-w-[12rem]">
                    <option value="">Semua jenis</option>
                    @foreach (\App\Models\RujukanPeraturan::JENIS as $k => $v)
                        <option value="{{ $k }}" @selected(request('jenis') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <button class="btn-secondary">Tapis</button>
                @if (request()->hasAny(['cari', 'jenis']))<a href="{{ route('rujukan.index') }}" class="text-sm text-slate-500 hover:underline">Set semula</a>@endif
            </form>
            @if ($bolehUrus)
                <a href="{{ route('rujukan.create') }}" class="btn-primary">+ Tambah Rujukan</a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr><th>Kod</th><th>Tajuk</th><th>Jenis</th><th>Tahun</th><th class="text-center">Seksyen</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @forelse ($rujukan as $r)
                        <tr>
                            <td class="whitespace-nowrap font-mono text-xs">{{ $r->kod }}</td>
                            <td>
                                <a href="{{ route('rujukan.show', $r) }}" class="font-medium text-mbsp-700 hover:underline">{{ $r->tajuk }}</a>
                                @if ($r->penerbit)<div class="text-xs text-slate-500">{{ $r->penerbit }}</div>@endif
                            </td>
                            <td class="whitespace-nowrap">{{ \App\Models\RujukanPeraturan::JENIS[$r->jenis] ?? $r->jenis }}</td>
                            <td>{{ $r->tahun ?? '-' }}</td>
                            <td class="text-center">{{ $r->seksyen_count }}</td>
                            <td class="space-y-1">
                                @if ($r->teks_rasmi)
                                    <span class="badge bg-emerald-100 text-emerald-800">Teks Rasmi</span>
                                @else
                                    <span class="badge bg-amber-100 text-amber-800">Ringkasan – belum disahkan</span>
                                @endif
                                @unless ($r->aktif)<span class="badge bg-slate-200 text-slate-600">Tidak aktif</span>@endunless
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Tiada rujukan ditemui.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($rujukan->hasPages())<div class="border-t border-slate-200 px-5 py-3">{{ $rujukan->links() }}</div>@endif
    </div>
</x-layouts.app>
