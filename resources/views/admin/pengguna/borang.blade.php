@php $baru = ! $pengguna->exists; @endphp
<x-layouts.app :tajuk="$baru ? 'Pengguna Baru' : 'Kemas Kini Pengguna'">
    <form method="POST" action="{{ $baru ? route('admin.pengguna.store') : route('admin.pengguna.update', $pengguna) }}" class="card max-w-3xl">
        @csrf
        @unless ($baru) @method('PUT') @endunless
        <div class="card-header"><h2 class="card-title">Maklumat Pengguna</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-medan label="Nama Penuh" nama="name" wajib class="sm:col-span-2">
                <input id="name" name="name" value="{{ old('name', $pengguna->name) }}" required maxlength="255" class="input">
            </x-medan>
            <x-medan label="E-mel Rasmi" nama="email" wajib nota="Kod OTP log masuk dihantar ke e-mel ini.">
                <input id="email" name="email" type="email" value="{{ old('email', $pengguna->email) }}" required class="input">
            </x-medan>
            <x-medan label="No. Pekerja" nama="no_pekerja">
                <input id="no_pekerja" name="no_pekerja" value="{{ old('no_pekerja', $pengguna->no_pekerja) }}" maxlength="30" class="input">
            </x-medan>
            <x-medan label="Jawatan" nama="jawatan">
                <input id="jawatan" name="jawatan" value="{{ old('jawatan', $pengguna->jawatan) }}" maxlength="255" class="input">
            </x-medan>
            <x-medan label="No. Telefon" nama="telefon">
                <input id="telefon" name="telefon" value="{{ old('telefon', $pengguna->telefon) }}" maxlength="30" class="input">
            </x-medan>
            <x-medan label="Peranan" nama="peranan" wajib class="sm:col-span-2">
                <select id="peranan" name="peranan" required class="input">
                    @foreach (\App\Enums\Peranan::options() as $k => $v)
                        <option value="{{ $k }}" @selected(old('peranan', $pengguna->peranan?->value) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </x-medan>
            <x-medan label="{{ $baru ? 'Kata Laluan' : 'Kata Laluan Baharu (pilihan)' }}" nama="password" :wajib="$baru"
                     nota="Sekurang-kurangnya 12 aksara dengan huruf besar, huruf kecil, nombor dan simbol.">
                <input id="password" name="password" type="password" autocomplete="new-password" @required($baru) class="input">
            </x-medan>
            <x-medan label="Sahkan Kata Laluan" nama="password_confirmation" :wajib="$baru">
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required($baru) class="input">
            </x-medan>
            <label class="flex items-center gap-2 text-sm sm:col-span-2">
                <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $pengguna->aktif)) class="rounded border-slate-300 text-mbsp-700">
                Akaun aktif
            </label>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
            <a href="{{ route('admin.pengguna.index') }}" class="btn-secondary">Batal</a>
            <button class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
