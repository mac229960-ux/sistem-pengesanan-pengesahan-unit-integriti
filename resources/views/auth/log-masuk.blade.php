<x-layouts.guest tajuk="Log Masuk">
    <h2 class="mb-1 text-lg font-semibold">Log Masuk</h2>
    <p class="mb-6 text-sm text-slate-500">Masukkan e-mel rasmi dan kata laluan anda. Kod OTP akan dihantar ke e-mel.</p>
    <form method="POST" action="{{ route('login.post') }}" class="space-y-4">
        @csrf
        <x-medan label="E-mel" nama="email" wajib>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" class="input" placeholder="nama@mbsp.gov.my">
        </x-medan>
        <x-medan label="Kata Laluan" nama="password" wajib>
            <input id="password" name="password" type="password" required autocomplete="current-password" class="input">
        </x-medan>
        <label class="flex items-center gap-2 text-sm text-slate-600">
            <input type="checkbox" name="ingat" value="1" class="rounded border-slate-300 text-mbsp-700"> Ingat saya pada peranti ini
        </label>
        <button class="btn-primary w-full">Teruskan</button>
    </form>
</x-layouts.guest>
