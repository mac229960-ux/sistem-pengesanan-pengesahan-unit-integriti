@php
    use App\Enums\StatusAduan;
    $u = auth()->user();
    $tabs = ['maklumat' => 'Maklumat Aduan'];
    if ($siasatan) {
        $tabs += [
            'siasatan' => 'Siasatan ('.$aduan->aktiviti->count().')',
            'bukti' => 'Bukti ('.$aduan->bukti->count().')',
            'ai' => 'Cadangan AI',
            'cadangan' => 'Keputusan & Tindakan',
        ];
    }
    $tabs['sejarah'] = 'Sejarah';
    $tab = array_key_exists($tab, $tabs) ? $tab : 'maklumat';
@endphp
<x-layouts.app :tajuk="'Kes '.$aduan->no_rujukan">
    {{-- Ringkasan kes --}}
    <div class="card mb-4">
        <div class="flex flex-col gap-4 p-5 lg:flex-row lg:items-start lg:justify-between">
            <div class="min-w-0">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="font-mono text-sm font-semibold text-mbsp-700">{{ $aduan->no_rujukan }}</span>
                    <x-status-badge :status="$aduan->status" />
                    <x-sla-badge :aduan="$aduan" />
                    <span class="badge bg-slate-100 text-slate-700">Keutamaan: {{ \App\Models\Aduan::KEUTAMAAN[$aduan->keutamaan] }}</span>
                </div>
                <h2 class="mt-2 text-lg font-semibold text-slate-900">{{ $aduan->tajuk }}</h2>
                <div class="mt-1 text-sm text-slate-500">
                    {{ $aduan->kategori->nama }} · Diterima {{ $aduan->tarikh_terima->format('d/m/Y') }} melalui {{ $aduan->saluran->nama }}
                    · Tarikh akhir SLA <strong>{{ $aduan->tarikh_akhir_sla->format('d/m/Y') }}</strong>
                </div>
                <div class="mt-1 text-sm text-slate-500">Pegawai Siasatan: <strong class="text-slate-700">{{ $aduan->pegawaiSiasatan?->name ?? 'Belum diagihkan' }}</strong></div>
            </div>
            <div class="flex flex-wrap gap-2 lg:justify-end">
                @can('update', $aduan)<a href="{{ route('aduan.edit', $aduan) }}" class="btn-secondary btn-sm">Kemas Kini</a>@endcan
                @can('lihatSiasatan', $aduan)<a href="{{ route('aduan.cetak', $aduan) }}" target="_blank" class="btn-secondary btn-sm">Cetak PDF</a>@endcan
                @if ($u->can('siasat', $aduan) && $aduan->status === StatusAduan::Diagihkan)
                    <form method="POST" action="{{ route('aduan.mula-siasatan', $aduan) }}">@csrf<button class="btn-primary btn-sm">Mula Siasatan</button></form>
                @endif
            </div>
        </div>

        @if ($u->can('agih', $aduan) || $u->can('tidakDiteruskan', $aduan))
            <div class="grid gap-4 border-t border-slate-200 bg-slate-50 p-5 md:grid-cols-2">
                @can('agih', $aduan)
                    <form method="POST" action="{{ route('aduan.agih', $aduan) }}" class="space-y-2">
                        @csrf
                        <div class="text-sm font-semibold text-slate-700">{{ $aduan->pegawai_siasatan_id ? 'Agih Semula Kes' : 'Agih Kes kepada Pegawai Siasatan' }}</div>
                        <select name="pegawai_siasatan_id" class="input" required>
                            <option value="">— Pilih pegawai —</option>
                            @foreach ($pegawai as $p)
                                <option value="{{ $p->id }}" @selected($aduan->pegawai_siasatan_id === $p->id)>{{ $p->name }} ({{ $p->beban }} kes aktif)</option>
                            @endforeach
                        </select>
                        <input name="catatan" class="input" placeholder="Catatan / arahan (pilihan)">
                        <button class="btn-primary btn-sm">Agih</button>
                    </form>
                @endcan
                @can('tidakDiteruskan', $aduan)
                    <form method="POST" action="{{ route('aduan.tidak-diteruskan', $aduan) }}" class="space-y-2" onsubmit="return confirm('Tandakan aduan ini sebagai Tidak Diteruskan? Tindakan ini menutup kes.')">
                        @csrf
                        <div class="text-sm font-semibold text-slate-700">Tidak Diteruskan (di luar bidang kuasa / bukan isu integriti)</div>
                        <textarea name="sebab" rows="3" class="input" placeholder="Nyatakan sebab dan pihak yang dirujuk (jika ada)" required minlength="10"></textarea>
                        <button class="btn-danger btn-sm">Tandakan Tidak Diteruskan</button>
                    </form>
                @endcan
            </div>
        @endif
    </div>

    {{-- Tab --}}
    <div class="mb-4 overflow-x-auto border-b border-slate-200">
        <nav class="-mb-px flex gap-1">
            @foreach ($tabs as $k => $label)
                <a href="{{ route('aduan.show', [$aduan, 'tab' => $k]) }}" @class([
                    'whitespace-nowrap border-b-2 px-4 py-2 text-sm font-medium',
                    'border-mbsp-700 text-mbsp-700' => $tab === $k,
                    'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700' => $tab !== $k,
                ])>{{ $label }}</a>
            @endforeach
        </nav>
    </div>

    @include('aduan.tab.'.$tab)
</x-layouts.app>
