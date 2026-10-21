<x-layouts.app tajuk="Jejak Audit">
    <form method="GET" class="card mb-4">
        <div class="card-body grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
            <div><label class="label">Dari</label><input type="date" name="dari" value="{{ request('dari') }}" class="input"></div>
            <div><label class="label">Hingga</label><input type="date" name="hingga" value="{{ request('hingga') }}" class="input"></div>
            <div>
                <label class="label">Pengguna</label>
                <select name="user_id" class="input">
                    <option value="">Semua</option>
                    @foreach ($pengguna as $p)<option value="{{ $p->id }}" @selected((string) request('user_id') === (string) $p->id)>{{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div>
                <label class="label">Tindakan</label>
                <select name="tindakan" class="input">
                    <option value="">Semua</option>
                    @foreach ($awalan as $a)<option value="{{ $a }}" @selected(request('tindakan') === $a)>{{ $a }}.*</option>@endforeach
                </select>
            </div>
            <div><label class="label">No. Rujukan Aduan</label><input name="no_rujukan" value="{{ request('no_rujukan') }}" placeholder="UI/2026/…" class="input"></div>
            <div class="flex items-end gap-2">
                <button class="btn-primary">Tapis</button>
                <a href="{{ route('admin.audit.index') }}" class="btn-secondary">Set semula</a>
            </div>
        </div>
    </form>

    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Rekod Audit</h2>
            <span class="text-xs text-slate-500">Rekod audit adalah baca sahaja dan tidak boleh diubah.</span>
        </div>
        <div class="overflow-x-auto">
            <table class="table">
                <thead><tr><th>Masa</th><th>Pengguna</th><th>Tindakan</th><th>Keterangan</th><th>Rekod</th><th>IP</th></tr></thead>
                <tbody>
                    @forelse ($log as $l)
                        <tr x-data="{ buka: false }">
                            <td class="whitespace-nowrap text-xs">{{ $l->created_at?->format('d/m/Y H:i:s') }}</td>
                            <td class="whitespace-nowrap">{{ $l->pengguna?->name ?? 'Sistem' }}</td>
                            <td><code class="rounded bg-slate-100 px-1.5 py-0.5 text-xs">{{ $l->tindakan }}</code></td>
                            <td class="max-w-md">
                                <div>{{ $l->keterangan }}</div>
                                @if ($l->nilai_lama || $l->nilai_baru)
                                    <button type="button" @click="buka=!buka" class="mt-1 text-xs text-mbsp-700 hover:underline" x-text="buka ? 'Sembunyi perubahan' : 'Lihat perubahan'"></button>
                                    <div x-show="buka" x-cloak class="mt-2 grid gap-2 sm:grid-cols-2">
                                        <div>
                                            <div class="text-[11px] font-semibold uppercase text-slate-500">Nilai lama</div>
                                            <pre class="max-h-60 overflow-auto rounded bg-red-50 p-2 text-[11px] text-red-900">{{ $l->nilai_lama ? json_encode($l->nilai_lama, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
                                        </div>
                                        <div>
                                            <div class="text-[11px] font-semibold uppercase text-slate-500">Nilai baru</div>
                                            <pre class="max-h-60 overflow-auto rounded bg-emerald-50 p-2 text-[11px] text-emerald-900">{{ $l->nilai_baru ? json_encode($l->nilai_baru, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '-' }}</pre>
                                        </div>
                                    </div>
                                @endif
                            </td>
                            <td class="whitespace-nowrap text-xs">
                                @if ($l->model_type){{ $l->model_type }} #{{ $l->model_id }}@endif
                                @if ($l->aduan)<div><a href="{{ route('aduan.show', $l->aduan_id) }}" class="text-mbsp-700 hover:underline">{{ $l->aduan->no_rujukan }}</a></div>@endif
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs">{{ $l->ip }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-8 text-center text-slate-500">Tiada rekod audit.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($log->hasPages())<div class="border-t border-slate-200 px-5 py-3">{{ $log->links() }}</div>@endif
    </div>
</x-layouts.app>
