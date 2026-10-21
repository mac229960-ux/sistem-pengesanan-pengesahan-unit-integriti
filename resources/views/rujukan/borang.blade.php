@php $baru = ! $rujukan->exists; @endphp
<x-layouts.app :tajuk="$baru ? 'Tambah Rujukan' : 'Kemas Kini Rujukan'">
    <form method="POST" enctype="multipart/form-data" action="{{ $baru ? route('rujukan.store') : route('rujukan.update', $rujukan) }}" class="card max-w-4xl">
        @csrf
        @unless ($baru) @method('PUT') @endunless
        <div class="card-header"><h2 class="card-title">Maklumat Rujukan</h2></div>
        <div class="card-body grid gap-4 sm:grid-cols-2">
            <x-medan label="Kod" nama="kod" wajib nota="Contoh: PP-MBSP-KT-1995, PP-JPA-3-2023">
                <input id="kod" name="kod" value="{{ old('kod', $rujukan->kod) }}" required maxlength="50" class="input font-mono">
            </x-medan>
            <x-medan label="Jenis" nama="jenis" wajib>
                <select id="jenis" name="jenis" class="input" required>
                    @foreach (\App\Models\RujukanPeraturan::JENIS as $k => $v)
                        <option value="{{ $k }}" @selected(old('jenis', $rujukan->jenis) === $k)>{{ $v }}</option>
                    @endforeach
                </select>
            </x-medan>
            <x-medan label="Tajuk" nama="tajuk" wajib class="sm:col-span-2">
                <input id="tajuk" name="tajuk" value="{{ old('tajuk', $rujukan->tajuk) }}" required maxlength="255" class="input">
            </x-medan>
            <x-medan label="Penerbit / Pihak Berkuasa" nama="penerbit">
                <input id="penerbit" name="penerbit" value="{{ old('penerbit', $rujukan->penerbit) }}" maxlength="255" class="input">
            </x-medan>
            <x-medan label="Tahun" nama="tahun">
                <input id="tahun" name="tahun" type="number" value="{{ old('tahun', $rujukan->tahun) }}" min="1900" max="{{ date('Y') + 1 }}" class="input">
            </x-medan>
            <x-medan label="URL Sumber" nama="sumber_url" class="sm:col-span-2">
                <input id="sumber_url" name="sumber_url" type="url" value="{{ old('sumber_url', $rujukan->sumber_url) }}" maxlength="255" class="input" placeholder="https://www.jpa.gov.my/...">
            </x-medan>
            <x-medan label="Ringkasan" nama="ringkasan" class="sm:col-span-2">
                <textarea id="ringkasan" name="ringkasan" rows="3" class="input">{{ old('ringkasan', $rujukan->ringkasan) }}</textarea>
            </x-medan>

            <x-medan label="Fail Dokumen (PDF / TXT)" nama="fail" class="sm:col-span-2"
                     nota="Teks akan diekstrak dan dipecahkan kepada seksyen secara automatik (maks. 20 MB). PDF imbasan tanpa teks tidak dapat diproses.">
                <input id="fail" name="fail" type="file" accept=".pdf,.txt" class="block w-full text-sm file:mr-3 file:rounded-md file:border-0 file:bg-mbsp-50 file:px-3 file:py-2 file:text-mbsp-700">
                @if ($rujukan->nama_fail)<p class="mt-1 text-xs text-slate-600">Fail semasa: {{ $rujukan->nama_fail }}</p>@endif
            </x-medan>

            <div class="space-y-2 sm:col-span-2">
                @unless ($baru)
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="gantikan_seksyen" value="1" @checked(old('gantikan_seksyen')) class="rounded border-slate-300 text-mbsp-700">
                        Gantikan seksyen sedia ada dengan kandungan fail baharu
                    </label>
                @endunless
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="teks_rasmi" value="1" @checked(old('teks_rasmi', $rujukan->teks_rasmi)) class="rounded border-slate-300 text-mbsp-700">
                    Kandungan ialah <strong>teks rasmi</strong> (telah disemak dengan dokumen asal)
                </label>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="aktif" value="1" @checked(old('aktif', $rujukan->aktif)) class="rounded border-slate-300 text-mbsp-700">
                    Aktif (digunakan oleh enjin cadangan AI)
                </label>
            </div>
        </div>
        <div class="flex justify-end gap-2 border-t border-slate-200 px-5 py-3">
            <a href="{{ $baru ? route('rujukan.index') : route('rujukan.show', $rujukan) }}" class="btn-secondary">Batal</a>
            <button class="btn-primary">Simpan</button>
        </div>
    </form>
</x-layouts.app>
