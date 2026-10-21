@php $baru = ! $aduan->exists; @endphp
<x-layouts.app :tajuk="$baru ? 'Daftar Aduan Baru' : 'Kemas Kini Aduan '.$aduan->no_rujukan">
    <form method="POST" action="{{ $baru ? route('aduan.store') : route('aduan.update', $aduan) }}"
          x-data="borangAduan(@js(old('tanpa_nama', $aduan->tanpa_nama) ? true : false), @js(old('pihak', [])))" class="space-y-6">
        @csrf
        @unless ($baru) @method('PUT') @endunless

        <div class="card">
            <div class="card-header"><div class="card-title">1. Maklumat Penerimaan</div></div>
            <div class="card-body grid gap-4 md:grid-cols-3">
                <x-medan label="Saluran Aduan" nama="saluran_id" wajib>
                    <select id="saluran_id" name="saluran_id" class="input" required>
                        <option value="">— Pilih —</option>
                        @foreach ($saluran as $s)<option value="{{ $s->id }}" @selected(old('saluran_id', $aduan->saluran_id) == $s->id)>{{ $s->nama }}</option>@endforeach
                    </select>
                </x-medan>
                <x-medan label="No. Rujukan Saluran (jika ada)" nama="no_rujukan_saluran" nota="Cth: no. tiket e-Aduan / no. surat">
                    <input id="no_rujukan_saluran" name="no_rujukan_saluran" value="{{ old('no_rujukan_saluran', $aduan->no_rujukan_saluran) }}" class="input">
                </x-medan>
                <x-medan label="Tarikh Aduan Diterima" nama="tarikh_terima" wajib nota="SLA 6 bulan dikira dari tarikh ini">
                    <input id="tarikh_terima" type="date" name="tarikh_terima" max="{{ today()->toDateString() }}" value="{{ old('tarikh_terima', $aduan->tarikh_terima?->toDateString()) }}" class="input" required>
                </x-medan>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="card-title">2. Maklumat Pengadu</div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="hidden" name="tanpa_nama" value="0">
                    <input type="checkbox" name="tanpa_nama" value="1" x-model="tanpaNama" class="rounded border-slate-300 text-mbsp-700"> Aduan tanpa nama
                </label>
            </div>
            <div class="card-body">
                <p x-show="tanpaNama" class="rounded-md bg-slate-50 p-3 text-sm text-slate-600">Aduan tanpa nama diterima. Maklumat pengadu tidak akan disimpan.</p>
                <div x-show="!tanpaNama" class="grid gap-4 md:grid-cols-3">
                    <x-medan label="Nama Pengadu" nama="nama_pengadu" wajib class="md:col-span-2">
                        <input id="nama_pengadu" name="nama_pengadu" value="{{ old('nama_pengadu', $aduan->nama_pengadu) }}" class="input" :required="!tanpaNama">
                    </x-medan>
                    <x-medan label="No. Kad Pengenalan" nama="no_kp_pengadu">
                        <input id="no_kp_pengadu" name="no_kp_pengadu" value="{{ old('no_kp_pengadu', $aduan->no_kp_pengadu) }}" class="input" placeholder="xxxxxx-xx-xxxx">
                    </x-medan>
                    <x-medan label="No. Telefon" nama="telefon_pengadu">
                        <input id="telefon_pengadu" name="telefon_pengadu" value="{{ old('telefon_pengadu', $aduan->telefon_pengadu) }}" class="input">
                    </x-medan>
                    <x-medan label="E-mel" nama="emel_pengadu">
                        <input id="emel_pengadu" type="email" name="emel_pengadu" value="{{ old('emel_pengadu', $aduan->emel_pengadu) }}" class="input">
                    </x-medan>
                    <x-medan label="Alamat" nama="alamat_pengadu">
                        <input id="alamat_pengadu" name="alamat_pengadu" value="{{ old('alamat_pengadu', $aduan->alamat_pengadu) }}" class="input">
                    </x-medan>
                </div>
                <p class="mt-3 text-xs text-slate-500">🔒 Maklumat peribadi pengadu disulitkan dalam pangkalan data dan tidak dihantar kepada AI.</p>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><div class="card-title">3. Maklumat Aduan</div></div>
            <div class="card-body grid gap-4 md:grid-cols-3">
                <x-medan label="Kategori Aduan" nama="kategori_id" wajib>
                    <select id="kategori_id" name="kategori_id" class="input" required>
                        <option value="">— Pilih —</option>
                        @foreach ($kategori as $k)<option value="{{ $k->id }}" @selected(old('kategori_id', $aduan->kategori_id) == $k->id)>{{ $k->nama }}</option>@endforeach
                    </select>
                </x-medan>
                <x-medan label="Keutamaan" nama="keutamaan" wajib>
                    <select id="keutamaan" name="keutamaan" class="input">
                        @foreach (\App\Models\Aduan::KEUTAMAAN as $v => $l)<option value="{{ $v }}" @selected(old('keutamaan', $aduan->keutamaan) === $v)>{{ $l }}</option>@endforeach
                    </select>
                </x-medan>
                <x-medan label="Tarikh Kejadian" nama="tarikh_kejadian">
                    <input id="tarikh_kejadian" type="date" name="tarikh_kejadian" value="{{ old('tarikh_kejadian', $aduan->tarikh_kejadian?->toDateString()) }}" class="input">
                </x-medan>
                <x-medan label="Tajuk Aduan" nama="tajuk" wajib class="md:col-span-3">
                    <input id="tajuk" name="tajuk" value="{{ old('tajuk', $aduan->tajuk) }}" class="input" required maxlength="255">
                </x-medan>
                <x-medan label="Keterangan / Penjelasan Aduan" nama="keterangan" wajib class="md:col-span-3">
                    <textarea id="keterangan" name="keterangan" rows="6" class="input" required>{{ old('keterangan', $aduan->keterangan) }}</textarea>
                </x-medan>
                <x-medan label="Lokasi Aduan" nama="lokasi" wajib class="md:col-span-2">
                    <input id="lokasi" name="lokasi" value="{{ old('lokasi', $aduan->lokasi) }}" class="input" required>
                </x-medan>
                <x-medan label="Jabatan Terlibat" nama="jabatan_terlibat_id">
                    <select id="jabatan_terlibat_id" name="jabatan_terlibat_id" class="input">
                        <option value="">— Tidak pasti —</option>
                        @foreach ($jabatan as $j)<option value="{{ $j->id }}" @selected(old('jabatan_terlibat_id', $aduan->jabatan_terlibat_id) == $j->id)>{{ $j->nama }}</option>@endforeach
                    </select>
                </x-medan>
            </div>
        </div>

        @if ($baru)
            <div class="card">
                <div class="card-header"><div class="card-title">4. Pihak Terlibat <span class="text-red-600">*</span></div></div>
                <div class="card-body space-y-4">
                    @include('aduan.partials.carian-hr')
                    @error('pihak')<p class="error">{{ $message }}</p>@enderror
                    <template x-if="pihak.length === 0"><p class="text-sm text-slate-500">Belum ada pihak terlibat. Cari dalam Sistem HR atau tambah secara manual.</p></template>
                    <template x-for="(p, i) in pihak" :key="i">
                        <div class="grid gap-2 rounded-md border border-slate-200 p-3 md:grid-cols-12">
                            <input type="hidden" :name="`pihak[${i}][pegawai_hr_id]`" :value="p.pegawai_hr_id">
                            <input :name="`pihak[${i}][nama]`" x-model="p.nama" class="input md:col-span-3" placeholder="Nama" required>
                            <input :name="`pihak[${i}][no_pekerja]`" x-model="p.no_pekerja" class="input md:col-span-2" placeholder="No. pekerja">
                            <input :name="`pihak[${i}][jawatan]`" x-model="p.jawatan" class="input md:col-span-2" placeholder="Jawatan">
                            <input :name="`pihak[${i}][jabatan]`" x-model="p.jabatan" class="input md:col-span-2" placeholder="Jabatan">
                            <select :name="`pihak[${i}][peranan]`" x-model="p.peranan" class="input md:col-span-2">
                                @foreach (\App\Models\AduanPihakTerlibat::PERANAN as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
                            </select>
                            <button type="button" @click="pihak.splice(i,1)" class="btn-secondary btn-sm md:col-span-1">✕</button>
                        </div>
                    </template>
                    <button type="button" @click="tambah({})" class="btn-secondary btn-sm">+ Tambah secara manual</button>
                </div>
            </div>
        @endif

        <div class="flex justify-end gap-2">
            <a href="{{ $baru ? route('aduan.index') : route('aduan.show', $aduan) }}" class="btn-secondary">Batal</a>
            <button class="btn-primary">{{ $baru ? 'Daftar Aduan' : 'Simpan Perubahan' }}</button>
        </div>
    </form>

    @push('scripts')
    <script>
        function borangAduan(tanpaNama, pihakLama) {
            return {
                tanpaNama, pihak: pihakLama.length ? pihakLama : [],
                tambah(p) { this.pihak.push({ pegawai_hr_id: p.id ?? '', nama: p.nama ?? '', no_pekerja: p.no_pekerja ?? '', jawatan: p.jawatan ? (p.jawatan + (p.gred ? ' (' + p.gred + ')' : '')) : '', jabatan: p.jabatan ?? '', peranan: 'didakwa' }); },
            };
        }
    </script>
    @endpush
</x-layouts.app>
