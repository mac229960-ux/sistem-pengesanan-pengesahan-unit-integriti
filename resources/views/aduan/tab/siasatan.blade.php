@php $boleh = auth()->user()->can('siasat', $aduan); @endphp
<div class="grid gap-4 lg:grid-cols-3">
    <div class="space-y-4 lg:col-span-2">
        @forelse ($aduan->aktiviti as $a)
            <div class="card">
                <div class="card-header">
                    <div>
                        <div class="text-sm font-semibold">{{ $a->tajuk }}</div>
                        <div class="text-xs text-slate-500">{{ \App\Models\SiasatanAktiviti::JENIS[$a->jenis] ?? $a->jenis }} · {{ $a->tarikh_aktiviti->format('d/m/Y H:i') }} · {{ $a->pegawai->name }}</div>
                    </div>
                    @if ($boleh && ($a->user_id === auth()->id() || auth()->user()->isKetuaUnit()))
                        <form method="POST" action="{{ route('aktiviti.destroy', [$aduan, $a]) }}" onsubmit="return confirm('Hapus aktiviti ini?')">@csrf @method('DELETE')
                            <button class="text-xs text-red-600 hover:underline">Hapus</button>
                        </form>
                    @endif
                </div>
                <div class="card-body whitespace-pre-line text-sm text-slate-700">{{ $a->butiran }}</div>
            </div>
        @empty
            <div class="card p-10 text-center text-sm text-slate-500">Belum ada aktiviti siasatan direkodkan.</div>
        @endforelse
    </div>

    @if ($boleh)
        <div class="card h-fit">
            <div class="card-header"><div class="card-title">Rekod Aktiviti Siasatan</div></div>
            <form method="POST" action="{{ route('aktiviti.store', $aduan) }}" class="card-body space-y-3">
                @csrf
                <x-medan label="Jenis Aktiviti" nama="jenis" wajib>
                    <select name="jenis" id="jenis" class="input">
                        @foreach (\App\Models\SiasatanAktiviti::JENIS as $v => $l)<option value="{{ $v }}" @selected(old('jenis') === $v)>{{ $l }}</option>@endforeach
                    </select>
                </x-medan>
                <x-medan label="Tarikh & Masa" nama="tarikh_aktiviti" wajib>
                    <input type="datetime-local" id="tarikh_aktiviti" name="tarikh_aktiviti" value="{{ old('tarikh_aktiviti', now()->format('Y-m-d\TH:i')) }}" class="input" required>
                </x-medan>
                <x-medan label="Tajuk" nama="tajuk" wajib>
                    <input id="tajuk" name="tajuk" value="{{ old('tajuk') }}" class="input" required>
                </x-medan>
                <x-medan label="Butiran / Dapatan" nama="butiran" wajib>
                    <textarea id="butiran" name="butiran" rows="6" class="input" required>{{ old('butiran') }}</textarea>
                </x-medan>
                <button class="btn-primary w-full">Simpan Aktiviti</button>
            </form>
        </div>
    @endif
</div>
