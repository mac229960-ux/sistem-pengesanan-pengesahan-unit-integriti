<x-layouts.guest tajuk="Pengesahan OTP">
    <h2 class="mb-1 text-lg font-semibold">Pengesahan OTP</h2>
    <p class="mb-6 text-sm text-slate-500">Kod 6 digit telah dihantar ke <strong>{{ $emel }}</strong>. Kod sah selama {{ config('integriti.otp.minit') }} minit.</p>

    @if (session('info'))<div class="mb-4 rounded-md bg-sky-50 px-3 py-2 text-sm text-sky-800">{{ session('info') }}</div>@endif
    @if (session('otp_dev'))
        <div class="mb-4 rounded-md bg-amber-50 px-3 py-2 text-sm text-amber-900 ring-1 ring-amber-200">
            <strong>Mod pembangunan:</strong> Kod OTP anda ialah <code class="font-mono text-base font-bold">{{ session('otp_dev') }}</code>
            <div class="text-xs">(Paparan ini dimatikan dalam produksi — tetapkan INTEGRITI_OTP_PAPAR_DEV=false)</div>
        </div>
    @endif

    <form method="POST" action="{{ route('otp.sahkan') }}" class="space-y-4">
        @csrf
        <x-medan label="Kod OTP" nama="kod" wajib>
            <input id="kod" name="kod" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code"
                   class="input text-center font-mono text-2xl tracking-[0.5em]">
        </x-medan>
        <button class="btn-primary w-full">Sahkan & Log Masuk</button>
    </form>
    <div class="mt-4 flex items-center justify-between text-sm">
        <a href="{{ route('login') }}" class="text-slate-500 hover:underline">← Kembali</a>
        <form method="POST" action="{{ route('otp.semula') }}">@csrf
            <button class="text-mbsp-700 hover:underline">Hantar semula kod</button>
        </form>
    </div>
</x-layouts.guest>
