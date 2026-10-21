<x-layouts.app tajuk="Laporan & Analisis">
    @php
        $r = $ringkasan;
        $bolehLihatKes = $kes !== null;
    @endphp

    {{-- Penapis --}}
    <form method="GET" action="{{ route('laporan.index') }}" class="card mb-6">
        <div class="card-body grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <x-medan label="Tarikh Terima Dari" nama="dari">
                <input type="date" id="dari" name="dari" value="{{ $penapis['dari'] ?? '' }}" class="input">
            </x-medan>
            <x-medan label="Hingga" nama="hingga">
                <input type="date" id="hingga" name="hingga" value="{{ $penapis['hingga'] ?? '' }}" class="input">
            </x-medan>
            @foreach ([['kategori_id', 'Kategori', 'kategori'], ['saluran_id', 'Saluran', 'saluran'], ['jabatan_id', 'Jabatan Terlibat', 'jabatan'], ['status', 'Status', 'status'], ['pegawai_id', 'Pegawai Siasatan', 'pegawai']] as [$nama, $label, $kunci])
                @continue($kunci === 'pegawai' && $pilihan['pegawai']->isEmpty())
                <x-medan :label="$label" :nama="$nama">
                    <select id="{{ $nama }}" name="{{ $nama }}" class="input">
                        <option value="">– Semua –</option>
                        @foreach ($pilihan[$kunci] as $id => $teks)
                            <option value="{{ $id }}" @selected((string) ($penapis[$nama] ?? '') === (string) $id)>{{ $teks }}</option>
                        @endforeach
                    </select>
                </x-medan>
            @endforeach
        </div>
        <div class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-5 py-3">
            <div class="flex gap-2">
                <button class="btn-primary">Tapis</button>
                <a href="{{ route('laporan.index') }}" class="btn-secondary">Set Semula</a>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('laporan.excel', $penapis) }}" class="btn-secondary">⬇ Excel</a>
                <a href="{{ route('laporan.pdf', $penapis) }}" class="btn-secondary">⬇ PDF</a>
            </div>
        </div>
    </form>

    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 xl:grid-cols-6">
        <x-stat label="Jumlah Aduan" :nilai="$r['jumlah']" />
        <x-stat label="Aktif" :nilai="$r['aktif']" warna="text-amber-700" />
        <x-stat label="Lewat SLA" :nilai="$r['lewat']" :warna="$r['lewat'] ? 'text-red-600' : 'text-slate-900'" />
        <x-stat label="Hampir Tamat SLA" :nilai="$r['hampir_sla']" warna="text-orange-600" />
        <x-stat label="Ditutup" :nilai="$r['ditutup']" warna="text-emerald-700" />
        <x-stat label="% Dalam SLA" :nilai="$r['peratus_dalam_sla'] === null ? '–' : $r['peratus_dalam_sla'].'%'" nota="KPI sasaran 100%" />
    </div>

    {{-- Analisis AI --}}
    <div class="card mt-6">
        <div class="card-header">
            <div>
                <h2 class="card-title">Analisis AI</h2>
                <p class="text-xs text-slate-500">Hanya statistik agregat (tanpa nama atau maklumat peribadi) dihantar kepada AI.</p>
            </div>
            <form method="POST" action="{{ route('laporan.analisis-ai', $penapis) }}" x-data="{ hantar: false }" @submit="hantar = true">
                @csrf
                <button class="btn-primary btn-sm" :disabled="hantar">
                    <span x-show="!hantar">✨ Jana Analisis AI</span>
                    <span x-show="hantar" x-cloak>Menjana…</span>
                </button>
            </form>
        </div>
        <div class="card-body">
            @if ($analisis)
                <div class="mb-3 text-xs text-slate-500">
                    Dijana {{ $analisis->created_at->format('d/m/Y H:i') }} · Penyedia: <span class="font-mono">{{ $analisis->penyedia }}</span>
                    @if ($analisis->model) · Model: <span class="font-mono">{{ $analisis->model }}</span>@endif
                </div>
                @if ($analisis->ralat)
                    <div class="rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">Analisis gagal: {{ $analisis->ralat }}</div>
                @else
                    <div class="prose-ai text-sm text-slate-700">{!! \Illuminate\Support\Str::markdown($analisis->jawapan ?? '', ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                    <p class="mt-4 rounded bg-slate-50 px-3 py-2 text-xs text-slate-500">Analisis AI adalah panduan sahaja dan perlu disemak oleh pegawai sebelum digunakan dalam laporan rasmi.</p>
                @endif
            @else
                <p class="text-sm text-slate-500">Belum ada analisis. Klik <em>Jana Analisis AI</em> untuk mendapatkan rumusan trend dan cadangan penambahbaikan berdasarkan penapis semasa.</p>
            @endif
        </div>
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        @include('laporan._jadual', ['tajuk' => 'Mengikut Status', 'lajur' => 'Status', 'data' => $ikutStatus])
        @include('laporan._jadual', ['tajuk' => 'Mengikut Kategori', 'lajur' => 'Kategori', 'data' => $ikutKategori])
        @include('laporan._jadual', ['tajuk' => 'Mengikut Saluran', 'lajur' => 'Saluran', 'data' => $ikutSaluran])
        @include('laporan._jadual', ['tajuk' => 'Mengikut Jabatan Terlibat', 'lajur' => 'Jabatan', 'data' => $ikutJabatan])
        @include('laporan._jadual', ['tajuk' => 'Keputusan Siasatan', 'lajur' => 'Keputusan', 'data' => $ikutKeputusan])

        @if ($ikutPegawai)
            <div class="card">
                <div class="card-header"><h3 class="card-title">Beban Kerja & Pematuhan SLA Pegawai</h3></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Pegawai Siasatan</th><th class="text-right">Jumlah</th><th class="text-right">Aktif</th><th class="text-right">Lewat</th><th class="text-right">Ditutup</th><th class="text-right">% SLA</th></tr></thead>
                        <tbody>
                        @foreach ($ikutPegawai as $p)
                            <tr>
                                <td>{{ $p['nama'] }}</td>
                                <td class="text-right">{{ $p['jumlah'] }}</td>
                                <td class="text-right">{{ $p['aktif'] }}</td>
                                <td class="text-right {{ $p['lewat'] ? 'font-semibold text-red-600' : '' }}">{{ $p['lewat'] }}</td>
                                <td class="text-right">{{ $p['ditutup'] }}</td>
                                <td class="text-right">{{ $p['peratus_sla'] === null ? '–' : $p['peratus_sla'].'%' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @if ($bolehLihatKes)
        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">Senarai Kes ({{ $kes->total() }})</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>No. Rujukan</th><th>Tarikh Terima</th><th>Kategori</th><th>Pegawai Siasatan</th><th>Status</th><th>Keputusan</th><th>SLA</th></tr></thead>
                    <tbody>
                    @forelse ($kes as $a)
                        <tr>
                            <td class="whitespace-nowrap"><a href="{{ route('aduan.show', $a) }}" class="font-medium text-mbsp-700 hover:underline">{{ $a->no_rujukan }}</a></td>
                            <td class="whitespace-nowrap">{{ $a->tarikh_terima->format('d/m/Y') }}</td>
                            <td>{{ $a->kategori?->nama }}</td>
                            <td>{{ $a->pegawaiSiasatan?->name ?? '–' }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                            <td class="text-xs">{{ $a->keputusan_siasatan?->label() ?? '–' }}</td>
                            <td><x-sla-badge :aduan="$a" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-500">Tiada kes sepadan dengan penapis.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($kes->hasPages())<div class="border-t border-slate-200 px-5 py-3">{{ $kes->links() }}</div>@endif
        </div>
    @endif
</x-layouts.app>
