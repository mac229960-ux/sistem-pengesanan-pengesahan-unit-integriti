@php
    use App\Enums\StatusAduan;
    $u = auth()->user();
    $semasa = $aduan->cadanganTindakan->first();
    $draf = $semasa && $semasa->status === 'draf' ? $semasa : null;
    $bolehSedia = $u->can('siasat', $aduan);
    // Borang dibuka dengan draf semasa, atau disalin daripada versi terakhir yang dikembalikan.
    $asas = $draf ?? ($semasa && $semasa->status === 'dikembalikan' ? $semasa : null);
    $terpilih = old('jenis_tindakan', $asas?->jenisTindakan->pluck('id')->all() ?? []);
    $kumpulan = $jenisTindakan->groupBy('kumpulan');
@endphp
<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        {{-- Sejarah cadangan & kelulusan --}}
        @forelse ($aduan->cadanganTindakan as $c)
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="text-sm font-semibold">Cadangan Tindakan v{{ $c->versi }}</div>
                        <div class="text-xs text-slate-500">Disediakan oleh {{ $c->penyedia->name }} · {{ $c->updated_at->format('d/m/Y H:i') }}{{ $c->dihantar_pada ? ' · Dihantar '.$c->dihantar_pada->format('d/m/Y H:i') : '' }}</div>
                    </div>
                    <span @class(['badge', 'bg-slate-100 text-slate-700' => $c->status === 'draf', 'bg-purple-100 text-purple-800' => $c->status === 'dihantar', 'bg-emerald-100 text-emerald-800' => $c->status === 'diluluskan', 'bg-orange-100 text-orange-800' => $c->status === 'dikembalikan'])>{{ \App\Models\CadanganTindakan::STATUS[$c->status] }}</span>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <div><span class="text-xs font-semibold uppercase text-slate-500">Keputusan Siasatan:</span> <strong>{{ $c->keputusan_siasatan->label() }}</strong></div>
                    <div><div class="text-xs font-semibold uppercase text-slate-500">Dapatan</div><div class="whitespace-pre-line">{{ $c->dapatan }}</div></div>
                    <div><div class="text-xs font-semibold uppercase text-slate-500">Justifikasi</div><div class="whitespace-pre-line">{{ $c->justifikasi }}</div></div>
                    @if ($c->peruntukan_peraturan)<div><span class="text-xs font-semibold uppercase text-slate-500">Peruntukan:</span> {{ $c->peruntukan_peraturan }}</div>@endif
                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">Tindakan Dicadangkan</div>
                        <ul class="list-disc pl-5">@foreach ($c->jenisTindakan as $jt)<li>{{ $jt->nama }}</li>@endforeach</ul>
                    </div>
                    @if ($c->ai_cadangan_id)<div class="text-xs text-slate-500">Merujuk Cadangan AI #{{ $c->ai_cadangan_id }}</div>@endif
                    @foreach ($aduan->kelulusan->where('cadangan_tindakan_id', $c->id) as $k)
                        <div @class(['rounded-md p-3', 'bg-emerald-50 text-emerald-900' => $k->keputusan === 'lulus', 'bg-orange-50 text-orange-900' => $k->keputusan !== 'lulus'])>
                            <strong>{{ $k->keputusan === 'lulus' ? 'Diluluskan' : 'Dikembalikan' }}</strong> oleh {{ $k->pelulus->name }} pada {{ $k->created_at->format('d/m/Y H:i') }}
                            @if ($k->ulasan)<div class="mt-1">{{ $k->ulasan }}</div>@endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="card p-10 text-center text-sm text-slate-500">Belum ada cadangan tindakan disediakan.</div>
        @endforelse

        {{-- Tindakan diambil --}}
        @if ($aduan->tindakanDiambil->isNotEmpty() || $u->can('rekodTindakan', $aduan))
            <div class="card">
                <div class="card-header"><div class="card-title">Tindakan Yang Diambil</div></div>
                <div class="overflow-x-auto">
                    <table class="table">
                        <thead><tr><th>Tarikh</th><th>Tindakan</th><th>No. Rujukan Surat</th><th>Butiran</th><th>Direkod Oleh</th></tr></thead>
                        <tbody>
                        @forelse ($aduan->tindakanDiambil as $t)
                            <tr><td class="whitespace-nowrap">{{ $t->tarikh_tindakan->format('d/m/Y') }}</td><td>{{ $t->jenisTindakan->nama }}</td><td>{{ $t->no_rujukan_surat ?? '—' }}</td><td>{{ $t->butiran }}</td><td>{{ $t->perekod->name }}</td></tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">Belum ada tindakan direkodkan.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                @can('rekodTindakan', $aduan)
                    <form method="POST" action="{{ route('tindakan.store', $aduan) }}" class="grid gap-2 border-t border-slate-200 bg-slate-50 p-4 md:grid-cols-4">
                        @csrf
                        <select name="jenis_tindakan_id" class="input md:col-span-2" required>
                            @foreach ($semasa?->jenisTindakan ?? [] as $jt)<option value="{{ $jt->id }}">{{ $jt->nama }}</option>@endforeach
                            <optgroup label="Lain-lain">
                                @foreach ($jenisTindakan as $jt)<option value="{{ $jt->id }}">{{ $jt->nama }}</option>@endforeach
                            </optgroup>
                        </select>
                        <input type="date" name="tarikh_tindakan" value="{{ today()->toDateString() }}" max="{{ today()->toDateString() }}" class="input" required>
                        <input name="no_rujukan_surat" class="input" placeholder="No. rujukan surat">
                        <textarea name="butiran" rows="2" class="input md:col-span-3" placeholder="Butiran tindakan yang telah dilaksanakan" required></textarea>
                        <button class="btn-primary btn-sm">Rekod Tindakan</button>
                    </form>
                @endcan
                @can('tutup', $aduan)
                    <form method="POST" action="{{ route('aduan.tutup', $aduan) }}" class="flex flex-wrap items-center gap-2 border-t border-slate-200 p-4" onsubmit="return confirm('Tutup kes ini sebagai selesai?')">
                        @csrf
                        <input name="catatan" class="input flex-1" placeholder="Catatan penutupan (pilihan)">
                        <button class="btn-success btn-sm">Tutup Kes (Selesai)</button>
                    </form>
                @endcan
            </div>
        @endif
    </div>

    <div class="space-y-4">
        {{-- Kelulusan (Ketua Unit) --}}
        @can('lulus', $aduan)
            <div class="card ring-2 ring-purple-300">
                <div class="card-header"><div class="card-title">Keputusan Kelulusan</div></div>
                <form method="POST" action="{{ route('kelulusan.store', $aduan) }}" class="card-body space-y-3" x-data="{ k: 'lulus' }">
                    @csrf
                    <div class="flex gap-4 text-sm">
                        <label class="flex items-center gap-2"><input type="radio" name="keputusan" value="lulus" x-model="k" class="text-emerald-600"> Lulus</label>
                        <label class="flex items-center gap-2"><input type="radio" name="keputusan" value="kembalikan" x-model="k" class="text-orange-600"> Kembalikan untuk pindaan</label>
                    </div>
                    <x-medan label="Ulasan" nama="ulasan">
                        <textarea id="ulasan" name="ulasan" rows="4" class="input" :required="k === 'kembalikan'" placeholder="Wajib jika dikembalikan"></textarea>
                    </x-medan>
                    <button class="btn-primary w-full">Sahkan Keputusan</button>
                    <p class="text-xs text-slate-500">Kelulusan adalah manual — sistem tidak meluluskan secara automatik.</p>
                </form>
            </div>
        @endcan

        {{-- Borang cadangan (Pegawai Siasatan) --}}
        @if ($bolehSedia)
            <div class="card">
                <div class="card-header"><div class="card-title">{{ $draf ? 'Kemas Kini Draf' : 'Sediakan Cadangan Tindakan' }}</div></div>
                <form method="POST" action="{{ route('cadangan.simpan', $aduan) }}" class="card-body space-y-3">
                    @csrf
                    <x-medan label="Keputusan Siasatan" nama="keputusan_siasatan" wajib>
                        <select id="keputusan_siasatan" name="keputusan_siasatan" class="input" required>
                            @foreach (\App\Enums\KeputusanSiasatan::options() as $v => $l)
                                <option value="{{ $v }}" @selected(old('keputusan_siasatan', $asas?->keputusan_siasatan?->value) === $v)>{{ $l }}</option>
                            @endforeach
                        </select>
                    </x-medan>
                    <x-medan label="Dapatan Siasatan" nama="dapatan" wajib>
                        <textarea id="dapatan" name="dapatan" rows="5" class="input" required>{{ old('dapatan', $asas?->dapatan) }}</textarea>
                    </x-medan>
                    <x-medan label="Justifikasi" nama="justifikasi" wajib>
                        <textarea id="justifikasi" name="justifikasi" rows="3" class="input" required>{{ old('justifikasi', $asas?->justifikasi) }}</textarea>
                    </x-medan>
                    <x-medan label="Peruntukan Peraturan / Pekeliling" nama="peruntukan_peraturan">
                        <input id="peruntukan_peraturan" name="peruntukan_peraturan" value="{{ old('peruntukan_peraturan', $asas?->peruntukan_peraturan) }}" class="input">
                    </x-medan>
                    @php $aiOk = $aduan->aiCadangan->whereNull('ralat'); @endphp
                    @if ($aiOk->isNotEmpty())
                        <x-medan label="Merujuk Cadangan AI" nama="ai_cadangan_id">
                            <select id="ai_cadangan_id" name="ai_cadangan_id" class="input">
                                <option value="">— Tiada —</option>
                                @foreach ($aiOk as $ai)<option value="{{ $ai->id }}" @selected(old('ai_cadangan_id', $asas?->ai_cadangan_id) == $ai->id)>#{{ $ai->id }} ({{ $ai->created_at->format('d/m/Y') }})</option>@endforeach
                            </select>
                        </x-medan>
                    @endif
                    <div>
                        <div class="label">Jenis Tindakan Dicadangkan <span class="text-red-600">*</span></div>
                        <div class="max-h-72 space-y-2 overflow-y-auto rounded-md border border-slate-200 p-3">
                            @foreach ($kumpulan as $k => $senarai)
                                <div class="text-xs font-semibold uppercase text-slate-500">{{ \App\Models\JenisTindakan::KUMPULAN[$k] ?? $k }}</div>
                                @foreach ($senarai as $jt)
                                    <label class="flex items-start gap-2 text-sm">
                                        <input type="checkbox" name="jenis_tindakan[]" value="{{ $jt->id }}" @checked(in_array($jt->id, $terpilih)) class="mt-0.5 rounded border-slate-300 text-mbsp-700">
                                        <span>{{ $jt->nama }}</span>
                                    </label>
                                @endforeach
                            @endforeach
                        </div>
                    </div>
                    <button class="btn-secondary w-full">Simpan Draf</button>
                </form>
                @if ($draf)
                    <form method="POST" action="{{ route('cadangan.hantar', $aduan) }}" class="border-t border-slate-200 p-5 pt-3" onsubmit="return confirm('Hantar cadangan kepada Ketua Unit Integriti untuk kelulusan?')">
                        @csrf
                        <button class="btn-primary w-full">Hantar untuk Kelulusan →</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</div>
