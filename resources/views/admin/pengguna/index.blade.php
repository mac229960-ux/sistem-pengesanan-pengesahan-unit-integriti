<x-layouts.app tajuk="Pengurusan Pengguna">
    @if ($kl = session('kata_laluan_sementara'))
        <div class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-900 ring-1 ring-amber-300">
            <div class="font-semibold">Kata laluan sementara untuk {{ $kl['nama'] }}</div>
            <div class="mt-1">Salin dan serahkan secara selamat. Kata laluan ini hanya dipaparkan <strong>sekali</strong>:</div>
            <code class="mt-2 inline-block select-all rounded bg-white px-3 py-1 font-mono text-base ring-1 ring-amber-200">{{ $kl['kata_laluan'] }}</code>
        </div>
    @endif

    <div class="card">
        <div class="card-header flex-wrap">
            <form method="GET" class="flex flex-1 flex-wrap items-center gap-2">
                <input type="search" name="cari" value="{{ request('cari') }}" placeholder="Nama, e-mel atau no. pekerja…" class="input max-w-xs">
                <select name="peranan" class="input max-w-[14rem]">
                    <option value="">Semua peranan</option>
                    @foreach (\App\Enums\Peranan::options() as $k => $v)
                        <option value="{{ $k }}" @selected(request('peranan') === $k)>{{ $v }}</option>
                    @endforeach
                </select>
                <select name="aktif" class="input max-w-[10rem]">
                    <option value="">Semua status</option>
                    <option value="1" @selected(request('aktif') === '1')>Aktif</option>
                    <option value="0" @selected(request('aktif') === '0')>Tidak aktif</option>
                </select>
                <button class="btn-secondary">Tapis</button>
            </form>
            <a href="{{ route('admin.pengguna.create') }}" class="btn-primary">+ Pengguna Baru</a>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Nama</th><th>E-mel</th><th>No. Pekerja</th><th>Peranan</th><th>Status</th><th>Log Masuk Terakhir</th><th></th></tr></thead>
                <tbody>
                    @forelse ($pengguna as $p)
                        <tr>
                            <td>
                                <div class="font-medium">{{ $p->name }}</div>
                                @if ($p->jawatan)<div class="text-xs text-slate-500">{{ $p->jawatan }}</div>@endif
                            </td>
                            <td>{{ $p->email }}</td>
                            <td class="font-mono text-xs">{{ $p->no_pekerja ?? '-' }}</td>
                            <td class="whitespace-nowrap">{{ $p->peranan->label() }}</td>
                            <td><span class="badge {{ $p->aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $p->aktif ? 'Aktif' : 'Tidak aktif' }}</span></td>
                            <td class="whitespace-nowrap text-xs">{{ $p->log_masuk_terakhir?->format('d/m/Y H:i') ?? '-' }}</td>
                            <td class="whitespace-nowrap text-right">
                                <a href="{{ route('admin.pengguna.edit', $p) }}" class="text-xs text-mbsp-700 hover:underline">Ubah</a>
                                <form method="POST" action="{{ route('admin.pengguna.reset', $p) }}" class="inline" onsubmit="return confirm('Tetapkan semula kata laluan {{ addslashes($p->name) }}?')">
                                    @csrf
                                    <button class="ml-3 text-xs text-amber-700 hover:underline">Reset kata laluan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-8 text-center text-slate-500">Tiada pengguna ditemui.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($pengguna->hasPages())<div class="border-t border-slate-200 px-5 py-3">{{ $pengguna->links() }}</div>@endif
    </div>
</x-layouts.app>
