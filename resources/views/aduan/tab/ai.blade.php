@php $penyedia = config('integriti.ai.provider'); @endphp
<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @forelse ($aduan->aiCadangan as $ai)
            <div class="card" x-data="{ prompt: false }">
                <div class="card-header">
                    <div>
                        <div class="text-sm font-semibold">Cadangan AI #{{ $ai->id }}</div>
                        <div class="text-xs text-slate-500">{{ $ai->created_at->format('d/m/Y H:i') }} · {{ $ai->pengguna->name }} · {{ $ai->penyedia }}{{ $ai->model ? ' / '.$ai->model : '' }}{{ $ai->tempoh_ms ? ' · '.number_format($ai->tempoh_ms / 1000, 1).'s' : '' }}</div>
                    </div>
                    <span @class(['badge', 'bg-slate-100 text-slate-700' => $ai->status_semakan === 'belum_disemak', 'bg-emerald-100 text-emerald-800' => $ai->status_semakan === 'diterima', 'bg-sky-100 text-sky-800' => $ai->status_semakan === 'diterima_sebahagian', 'bg-red-100 text-red-800' => $ai->status_semakan === 'ditolak'])>{{ \App\Models\AiCadangan::STATUS[$ai->status_semakan] }}</span>
                </div>
                <div class="card-body">
                    @if ($ai->ralat)
                        <div class="rounded-md bg-red-50 p-3 text-sm text-red-800">Gagal: {{ $ai->ralat }}</div>
                    @else
                        <div class="prose-ai text-sm leading-relaxed text-slate-700">{!! \Illuminate\Support\Str::markdown($ai->jawapan ?? '', ['html_input' => 'escape', 'allow_unsafe_links' => false]) !!}</div>
                        @if ($ai->rujukan_dipetik)
                            <div class="mt-4 rounded-md bg-slate-50 p-3 text-xs">
                                <div class="mb-1 font-semibold text-slate-600">Rujukan yang diberikan kepada AI:</div>
                                <ul class="space-y-0.5">
                                    @foreach ($ai->rujukan_dipetik as $r)<li><span class="font-mono font-semibold">[{{ $r['label'] }}]</span> {{ $r['tajuk'] }}</li>@endforeach
                                </ul>
                            </div>
                        @endif
                    @endif
                    <button type="button" @click="prompt=!prompt" class="mt-3 text-xs text-slate-500 hover:underline">Lihat data yang dihantar kepada AI</button>
                    <pre x-show="prompt" x-cloak class="mt-2 max-h-96 overflow-auto whitespace-pre-wrap rounded bg-slate-900 p-3 text-[11px] text-slate-100">{{ $ai->prompt }}</pre>

                    @if (! $ai->ralat)
                        @if ($ai->disemak_oleh)
                            <div class="mt-3 text-xs text-slate-500">Disemak oleh {{ $ai->penyemak?->name }} pada {{ $ai->disemak_pada?->format('d/m/Y H:i') }}{{ $ai->ulasan_pegawai ? ': '.$ai->ulasan_pegawai : '' }}</div>
                        @endif
                        <form method="POST" action="{{ route('ai.semak', $ai) }}" class="mt-3 grid gap-2 border-t border-slate-100 pt-3 md:grid-cols-4">
                            @csrf
                            <select name="status_semakan" class="input">
                                @foreach (['diterima' => 'Terima', 'diterima_sebahagian' => 'Terima Sebahagian', 'ditolak' => 'Tolak'] as $v => $l)
                                    <option value="{{ $v }}" @selected($ai->status_semakan === $v)>{{ $l }}</option>
                                @endforeach
                            </select>
                            <input name="ulasan_pegawai" value="{{ $ai->ulasan_pegawai }}" class="input md:col-span-2" placeholder="Ulasan pegawai terhadap cadangan AI">
                            <button class="btn-secondary btn-sm">Rekod Semakan</button>
                        </form>
                    @endif
                </div>
            </div>
        @empty
            <div class="card p-10 text-center text-sm text-slate-500">Belum ada cadangan AI dijana bagi kes ini.</div>
        @endforelse
    </div>

    <div class="space-y-4">
        <div class="card">
            <div class="card-header"><div class="card-title">Jana Cadangan AI</div></div>
            <form method="POST" action="{{ route('ai.jana', $aduan) }}" class="card-body space-y-3" x-data="{ hantar: false }" @submit="hantar = true">
                @csrf
                <p class="text-sm text-slate-600">AI akan menganalisis fakta kes, aktiviti siasatan dan bukti, kemudian merujuk Pustaka Peraturan untuk mencadangkan tindakan.</p>
                <x-medan label="Soalan tambahan (pilihan)" nama="soalan">
                    <textarea id="soalan" name="soalan" rows="3" class="input" placeholder="Cth: Adakah kes ini perlu dirujuk kepada SPRM?"></textarea>
                </x-medan>
                <button class="btn-primary w-full" :disabled="hantar"><span x-show="!hantar">Jana Cadangan</span><span x-show="hantar" x-cloak>Sedang menjana… (boleh mengambil masa 1–2 minit)</span></button>
            </form>
        </div>
        <div class="card p-4 text-xs text-slate-600">
            <div class="mb-1 font-semibold text-slate-700">Kawalan & Privasi</div>
            <ul class="list-disc space-y-1 pl-4">
                <li>Penyedia AI semasa: <strong>{{ $penyedia }}</strong>{{ $penyedia === 'mock' ? ' (mod demo)' : '' }}</li>
                <li>Maklumat pengadu <strong>tidak</strong> dihantar kepada AI.</li>
                @if (config('integriti.ai.anonimkan'))<li>Nama pihak terlibat dianonimkan (Pegawai A, B…). No. KP & telefon dalam teks ditapis.</li>@endif
                <li>Cadangan AI adalah panduan sahaja. Keputusan dibuat oleh Pegawai Siasatan dan diluluskan oleh Ketua Unit.</li>
                <li>Setiap penjanaan direkod dalam jejak audit.</li>
            </ul>
        </div>
    </div>
</div>
