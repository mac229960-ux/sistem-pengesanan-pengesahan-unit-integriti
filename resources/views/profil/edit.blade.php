<x-layouts.app tajuk="Profil Saya">
    <div class="grid gap-6 lg:grid-cols-2">
        <div class="card">
            <div class="card-header"><h2 class="card-title">Maklumat Akaun</h2></div>
            <dl class="card-body grid grid-cols-3 gap-x-4 gap-y-3 text-sm">
                <dt class="text-slate-500">Nama</dt><dd class="col-span-2 font-medium">{{ $user->name }}</dd>
                <dt class="text-slate-500">E-mel</dt><dd class="col-span-2">{{ $user->email }}</dd>
                <dt class="text-slate-500">No. Pekerja</dt><dd class="col-span-2">{{ $user->no_pekerja ?? '–' }}</dd>
                <dt class="text-slate-500">Jawatan</dt><dd class="col-span-2">{{ $user->jawatan ?? '–' }}</dd>
                <dt class="text-slate-500">Telefon</dt><dd class="col-span-2">{{ $user->telefon ?? '–' }}</dd>
                <dt class="text-slate-500">Peranan</dt><dd class="col-span-2"><span class="badge bg-mbsp-100 text-mbsp-800">{{ $user->peranan->label() }}</span></dd>
                <dt class="text-slate-500">Log masuk terakhir</dt><dd class="col-span-2">{{ $user->log_masuk_terakhir?->format('d/m/Y H:i') ?? '–' }} @if($user->ip_terakhir)<span class="text-xs text-slate-400">({{ $user->ip_terakhir }})</span>@endif</dd>
            </dl>
            <p class="border-t border-slate-200 px-5 py-3 text-xs text-slate-500">Untuk mengemas kini nama, jawatan atau peranan, sila hubungi Pentadbir Sistem.</p>
        </div>

        <div class="card">
            <div class="card-header"><h2 class="card-title">Tukar Kata Laluan</h2></div>
            <form method="POST" action="{{ route('profil.kata-laluan') }}" class="card-body space-y-4">
                @csrf @method('PUT')
                <x-medan label="Kata Laluan Semasa" nama="kata_laluan_semasa" wajib>
                    <input type="password" id="kata_laluan_semasa" name="kata_laluan_semasa" required autocomplete="current-password" class="input">
                </x-medan>
                <x-medan label="Kata Laluan Baharu" nama="password" wajib nota="Sekurang-kurangnya 12 aksara, mengandungi huruf besar, huruf kecil, nombor dan simbol.">
                    <input type="password" id="password" name="password" required autocomplete="new-password" class="input">
                </x-medan>
                <x-medan label="Sahkan Kata Laluan Baharu" nama="password_confirmation" wajib>
                    <input type="password" id="password_confirmation" name="password_confirmation" required autocomplete="new-password" class="input">
                </x-medan>
                <button class="btn-primary">Kemas Kini Kata Laluan</button>
            </form>
        </div>
    </div>
</x-layouts.app>
