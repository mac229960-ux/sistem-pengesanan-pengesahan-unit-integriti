<x-layouts.app tajuk="Dashboard">
@if ($mod === 'pentadbir')
    {{-- Pentadbir Sistem: statistik sistem sahaja, tiada butiran kes --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
        <x-stat label="Pengguna Aktif" :nilai="$sistem['pengguna_aktif'].' / '.$sistem['pengguna_jumlah']" :href="route('admin.pengguna.index')" />
        <x-stat label="Rujukan Peraturan Aktif" :nilai="$sistem['rujukan']" :nota="$sistem['rujukan_rasmi'].' teks rasmi'" :href="route('rujukan.index')" />
        <x-stat label="Entri Audit Hari Ini" :nilai="$sistem['audit_hari_ini']" :href="route('admin.audit.index')" />
        <x-stat label="Log Masuk Gagal Hari Ini" :nilai="$sistem['log_masuk_gagal']" :warna="$sistem['log_masuk_gagal'] ? 'text-red-600' : 'text-slate-900'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Pengguna Mengikut Peranan</h2></div>
            <div class="card-body">
                <dl class="space-y-2 text-sm">
                    @foreach ($sistem['ikut_peranan'] as $label => $n)
                        <div class="flex justify-between"><dt class="text-slate-600">{{ $label }}</dt><dd class="font-semibold">{{ $n }}</dd></div>
                    @endforeach
                </dl>
            </div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Konfigurasi Integrasi</h2></div>
            <div class="card-body text-sm">
                <dl class="space-y-2">
                    <div class="flex justify-between"><dt class="text-slate-600">Penyedia AI</dt><dd class="font-mono font-semibold">{{ $sistem['penyedia_ai'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Sistem HR Bersepadu</dt><dd class="font-mono font-semibold">{{ $sistem['hr_driver'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">SLA penyelesaian</dt><dd class="font-semibold">{{ config('integriti.sla_bulan') }} bulan</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-600">Tempoh simpanan rekod</dt><dd class="font-semibold">{{ config('integriti.retensi_tahun') }} tahun</dd></div>
                </dl>
                <p class="mt-4 text-xs text-slate-500">Pentadbir Sistem tidak mempunyai akses kepada butiran kes aduan.</p>
            </div>
        </div>
        <div class="card lg:col-span-1">
            <div class="card-header"><h2 class="card-title">Aktiviti Terkini</h2><a href="{{ route('admin.audit.index') }}" class="text-xs text-mbsp-700 hover:underline">Lihat semua</a></div>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($auditTerkini as $log)
                    <li class="px-5 py-2">
                        <div class="font-mono text-xs text-slate-500">{{ $log->created_at->format('d/m H:i') }} · {{ $log->tindakan }}</div>
                        <div class="truncate">{{ $log->pengguna?->name ?? 'Sistem' }}</div>
                    </li>
                @empty
                    <li class="px-5 py-4 text-slate-500">Tiada aktiviti.</li>
                @endforelse
            </ul>
        </div>
    </div>
@else
    @php $r = $ringkasan; @endphp

    @if ($mod === 'ketua_unit' && ($menungguKelulusan ?? 0) > 0)
        <a href="{{ route('kelulusan.index') }}" class="mb-4 flex items-center justify-between rounded-md bg-purple-50 px-4 py-3 text-sm text-purple-900 ring-1 ring-purple-200 hover:bg-purple-100">
            <span><strong>{{ $menungguKelulusan }}</strong> cadangan tindakan menunggu kelulusan anda.</span>
            <span class="font-medium">Semak sekarang →</span>
        </a>
    @endif

    <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
        <x-stat label="{{ $mod === 'pegawai_siasatan' ? 'Kes Aktif Saya' : 'Kes Aktif' }}" :nilai="$r['aktif']" :nota="$r['jumlah'].' jumlah keseluruhan'" />
        <x-stat label="Baru Diterima" :nilai="$r['baru']" warna="text-sky-700" />
        <x-stat label="Dalam Siasatan" :nilai="$r['dalam_siasatan']" warna="text-amber-700" />
        <x-stat label="Menunggu Kelulusan" :nilai="$r['menunggu_kelulusan']" warna="text-purple-700" />
        <x-stat label="Hampir Tamat SLA (≤30 hari)" :nilai="$r['hampir_sla']" :warna="$r['hampir_sla'] ? 'text-orange-600' : 'text-slate-900'" />
        <x-stat label="Lewat SLA" :nilai="$r['lewat']" :warna="$r['lewat'] ? 'text-red-600' : 'text-slate-900'" />
        <x-stat label="Selesai Tahun {{ now()->year }}" :nilai="$r['selesai_tahun_ini']" warna="text-emerald-700" />
        <x-stat label="KPI: Selesai dalam {{ config('integriti.sla_bulan') }} Bulan" :nilai="$r['peratus_dalam_sla'] === null ? '–' : $r['peratus_dalam_sla'].'%'"
                :warna="$r['peratus_dalam_sla'] === null ? 'text-slate-400' : ($r['peratus_dalam_sla'] >= 100 ? 'text-emerald-700' : ($r['peratus_dalam_sla'] >= 80 ? 'text-amber-600' : 'text-red-600'))"
                :nota="'Sasaran 100% · '.$r['ditutup_dalam_sla'].'/'.$r['ditutup'].' kes ditutup'" />
    </div>

    <div class="mt-6 grid gap-6 lg:grid-cols-3">
        <div class="card lg:col-span-2">
            <div class="card-header"><h2 class="card-title">Trend 12 Bulan: Diterima vs Selesai</h2></div>
            <div class="card-body"><div class="h-64"><canvas id="cartaTrend"></canvas></div></div>
        </div>
        <div class="card">
            <div class="card-header"><h2 class="card-title">Mengikut Status</h2></div>
            <div class="card-body"><div class="h-64"><canvas id="cartaStatus"></canvas></div></div>
        </div>
        <div class="card lg:col-span-3">
            <div class="card-header"><h2 class="card-title">Mengikut Kategori</h2></div>
            <div class="card-body"><div class="h-72"><canvas id="cartaKategori"></canvas></div></div>
        </div>
    </div>

    @if ($mod === 'ketua_unit' && isset($belumDiagih) && $belumDiagih->isNotEmpty())
        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">Aduan Belum Diagihkan</h2><a href="{{ route('aduan.index', ['status' => 'baru']) }}" class="text-xs text-mbsp-700 hover:underline">Lihat semua</a></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>No. Rujukan</th><th>Tarikh Terima</th><th>Kategori</th><th>Tajuk</th><th>SLA</th></tr></thead>
                    <tbody>
                    @foreach ($belumDiagih as $a)
                        <tr>
                            <td class="whitespace-nowrap"><a href="{{ route('aduan.show', $a) }}" class="font-medium text-mbsp-700 hover:underline">{{ $a->no_rujukan }}</a></td>
                            <td class="whitespace-nowrap">{{ $a->tarikh_terima->format('d/m/Y') }}</td>
                            <td>{{ $a->kategori?->nama }}</td>
                            <td class="max-w-md truncate">{{ $a->tajuk }}</td>
                            <td><x-sla-badge :aduan="$a" /></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @isset($tugasan)
        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">Tugasan Saya</h2><a href="{{ route('aduan.index') }}" class="text-xs text-mbsp-700 hover:underline">Senarai penuh</a></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>No. Rujukan</th><th>Kategori</th><th>Tajuk</th><th>Status</th><th>SLA</th></tr></thead>
                    <tbody>
                    @forelse ($tugasan as $a)
                        <tr>
                            <td class="whitespace-nowrap"><a href="{{ route('aduan.show', $a) }}" class="font-medium text-mbsp-700 hover:underline">{{ $a->no_rujukan }}</a></td>
                            <td>{{ $a->kategori?->nama }}</td>
                            <td class="max-w-md truncate">{{ $a->tajuk }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                            <td><x-sla-badge :aduan="$a" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">Tiada kes aktif diagihkan kepada anda.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endisset

    @isset($kesSla)
        <div class="card mt-6">
            <div class="card-header"><h2 class="card-title">Kes Hampir Tamat / Melepasi SLA</h2></div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>No. Rujukan</th><th>Kategori</th><th>Pegawai Siasatan</th><th>Status</th><th>Tarikh Akhir</th><th>SLA</th></tr></thead>
                    <tbody>
                    @forelse ($kesSla as $a)
                        <tr>
                            <td class="whitespace-nowrap"><a href="{{ route('aduan.show', $a) }}" class="font-medium text-mbsp-700 hover:underline">{{ $a->no_rujukan }}</a></td>
                            <td>{{ $a->kategori?->nama }}</td>
                            <td>{{ $a->pegawaiSiasatan?->name ?? '– Belum diagih –' }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                            <td class="whitespace-nowrap">{{ $a->tarikh_akhir_sla->format('d/m/Y') }}</td>
                            <td><x-sla-badge :aduan="$a" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">Tiada kes yang hampir tamat SLA. 👍</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endisset

    @isset($terkini)
        <div class="card mt-6">
            <div class="card-header">
                <h2 class="card-title">Aduan Terkini Didaftarkan</h2>
                <a href="{{ route('aduan.create') }}" class="btn-primary btn-sm">+ Daftar Aduan Baru</a>
            </div>
            <div class="overflow-x-auto">
                <table class="table">
                    <thead><tr><th>No. Rujukan</th><th>Tarikh Terima</th><th>Saluran</th><th>Kategori</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($terkini as $a)
                        <tr>
                            <td class="whitespace-nowrap"><a href="{{ route('aduan.show', $a) }}" class="font-medium text-mbsp-700 hover:underline">{{ $a->no_rujukan }}</a></td>
                            <td class="whitespace-nowrap">{{ $a->tarikh_terima->format('d/m/Y') }}</td>
                            <td>{{ $a->saluran?->nama }}</td>
                            <td>{{ $a->kategori?->nama }}</td>
                            <td><x-status-badge :status="$a->status" /></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">Tiada aduan didaftarkan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endisset

    @if ($mod === 'pengurusan')
        <p class="mt-6 text-xs text-slate-500">Paparan pengurusan menunjukkan data agregat sahaja. Butiran kes adalah SULIT dan terhad kepada Unit Integriti. Lihat <a href="{{ route('laporan.index') }}" class="text-mbsp-700 hover:underline">Laporan & Analisis</a> untuk pecahan terperinci.</p>
    @endif

    @push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const warna = ['#1549d6', '#f59e0b', '#10b981', '#8b5cf6', '#ef4444', '#06b6d4', '#f97316', '#64748b', '#ec4899', '#84cc16', '#0ea5e9'];
            const trend = @json($trend);
            const status = @json($ikutStatus);
            const kategori = @json($ikutKategori);

            new Chart(document.getElementById('cartaTrend'), {
                type: 'line',
                data: {
                    labels: trend.label,
                    datasets: [
                        { label: 'Diterima', data: trend.diterima, borderColor: '#1549d6', backgroundColor: 'rgba(21,73,214,.1)', fill: true, tension: .3 },
                        { label: 'Selesai / Ditutup', data: trend.selesai, borderColor: '#10b981', backgroundColor: 'rgba(16,185,129,.1)', fill: true, tension: .3 },
                    ],
                },
                options: { maintainAspectRatio: false, scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } },
            });

            const st = Object.entries(status).filter(([, n]) => n > 0);
            new Chart(document.getElementById('cartaStatus'), {
                type: 'doughnut',
                data: { labels: st.map(x => x[0]), datasets: [{ data: st.map(x => x[1]), backgroundColor: warna }] },
                options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } } } },
            });

            new Chart(document.getElementById('cartaKategori'), {
                type: 'bar',
                data: { labels: Object.keys(kategori), datasets: [{ label: 'Bilangan aduan', data: Object.values(kategori), backgroundColor: '#337dfc' }] },
                options: { maintainAspectRatio: false, indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } },
            });
        });
    </script>
    @endpush
@endif
</x-layouts.app>
