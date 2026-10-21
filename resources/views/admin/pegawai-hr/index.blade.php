<x-layouts.app tajuk="Data Pegawai (Sistem HR)">
    @if ($driver === 'mock')
        <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-300">
            <strong>DATA MOCK.</strong> Senarai ini ialah data rekaan sementara menunggu integrasi dengan Sistem HR Bersepadu MBSP.
            Penyambung ditetapkan melalui <code>HR_DRIVER</code> dalam fail <code>.env</code> (<code>mock</code> atau <code>api</code>).
        </div>
    @else
        <div class="mb-4 rounded-md bg-sky-50 px-4 py-3 text-sm text-sky-900 ring-1 ring-sky-200">
            Data dicerminkan daripada Sistem HR Bersepadu MBSP (<code>HR_DRIVER={{ $driver }}</code>).
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <form method="GET" class="flex flex-1 items-center gap-2">
                <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Nama, no. pekerja atau jawatan…" class="input max-w-xs">
                <button class="btn-secondary">Cari</button>
            </form>
            <span class="text-xs text-slate-500">{{ $pegawai->total() }} rekod</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>No. Pekerja</th><th>Nama</th><th>Jawatan</th><th>Gred</th><th>Jabatan</th><th>E-mel</th><th>Status</th><th>Disegerak</th></tr></thead>
                <tbody>
                    @forelse ($pegawai as $p)
                        <tr>
                            <td class="font-mono text-xs">{{ $p->no_pekerja }}</td>
                            <td class="font-medium">{{ $p->nama }}</td>
                            <td>{{ $p->jawatan ?? '-' }}</td>
                            <td>{{ $p->gred ?? '-' }}</td>
                            <td>{{ $p->jabatan?->nama ?? '-' }}</td>
                            <td class="text-xs">{{ $p->emel ?? '-' }}</td>
                            <td><span class="badge {{ $p->status_perkhidmatan === 'aktif' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ ucfirst($p->status_perkhidmatan) }}</span></td>
                            <td class="whitespace-nowrap text-xs">{{ $p->disegerak_pada?->format('d/m/Y H:i') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="py-8 text-center text-slate-500">Tiada rekod ditemui.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pegawai->hasPages())<div class="border-t border-slate-200 px-5 py-3">{{ $pegawai->links() }}</div>@endif
    </div>
</x-layouts.app>
