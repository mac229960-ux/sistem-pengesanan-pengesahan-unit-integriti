<x-layouts.app :tajuk="$label">
    @php
        $tindakan = $jenis === 'tindakan';
        $kumpulan = \App\Models\JenisTindakan::KUMPULAN;
    @endphp
    <div class="mb-4 rounded-md bg-slate-50 px-4 py-3 text-sm text-slate-600 ring-1 ring-slate-200">
        Rekod tidak boleh dihapuskan kerana mungkin dirujuk oleh kes sedia ada. Nyahaktifkan rekod untuk menyembunyikannya daripada borang baharu.
    </div>

    <div class="card mb-6">
        <div class="card-header"><h2 class="card-title">Senarai {{ $label }} ({{ $rekod->count() }})</h2></div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr>
                        @if ($tindakan)<th class="w-16">Susunan</th>@endif
                        <th>Kod</th><th>Nama</th>
                        @if ($tindakan)<th>Kumpulan</th>@endif
                        <th>Status</th><th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rekod as $r)
                        <tr x-data="{ edit: false }">
                            <template x-if="!edit">
                                <td colspan="{{ $tindakan ? 6 : 4 }}" class="!p-0">
                                    <div class="flex items-start gap-3 px-3 py-2">
                                        @if ($tindakan)<div class="w-12 shrink-0 text-slate-500">{{ $r->susunan }}</div>@endif
                                        <div class="w-40 shrink-0 font-mono text-xs">{{ $r->kod }}</div>
                                        <div class="flex-1">
                                            <div>{{ $r->nama }}</div>
                                            @if ($adaKeterangan && $r->keterangan)<div class="text-xs text-slate-500">{{ $r->keterangan }}</div>@endif
                                            @if ($tindakan && $r->rujukan_peraturan)<div class="text-xs text-slate-500">Rujukan: {{ $r->rujukan_peraturan }}</div>@endif
                                        </div>
                                        @if ($tindakan)<div class="w-48 shrink-0 text-xs">{{ $kumpulan[$r->kumpulan] ?? $r->kumpulan }}</div>@endif
                                        <div class="w-24 shrink-0"><span class="badge {{ $r->aktif ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">{{ $r->aktif ? 'Aktif' : 'Tidak aktif' }}</span></div>
                                        <button type="button" @click="edit=true" class="shrink-0 text-xs text-mbsp-700 hover:underline">Ubah</button>
                                    </div>
                                </td>
                            </template>
                            <template x-if="edit">
                                <td colspan="{{ $tindakan ? 6 : 4 }}" class="bg-slate-50">
                                    <form method="POST" action="{{ route('admin.data.update', [$jenis, $r->id]) }}" class="grid gap-2 sm:grid-cols-6">
                                        @csrf @method('PUT')
                                        @include('admin.data._medan', ['r' => $r])
                                        <div class="flex justify-end gap-2 sm:col-span-6">
                                            <button type="button" @click="edit=false" class="btn-secondary btn-sm">Batal</button>
                                            <button class="btn-primary btn-sm">Simpan</button>
                                        </div>
                                    </form>
                                </td>
                            </template>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.data.store', $jenis) }}" class="card">
        @csrf
        <div class="card-header"><h2 class="card-title">Tambah {{ $label }}</h2></div>
        <div class="card-body grid gap-2 sm:grid-cols-6">
            @include('admin.data._medan', ['r' => null])
        </div>
        <div class="flex justify-end border-t border-slate-200 px-5 py-3"><button class="btn-primary">Tambah</button></div>
    </form>
</x-layouts.app>
