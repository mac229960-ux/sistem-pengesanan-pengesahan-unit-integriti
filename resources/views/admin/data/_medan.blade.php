{{-- Medan borang data rujukan. $r = rekod sedia ada atau null untuk tambah. --}}
@php $baru = $r === null; @endphp
<div class="sm:col-span-1">
    <label class="label">Kod <span class="text-red-600">*</span></label>
    <input name="kod" value="{{ $baru ? old('kod') : $r->kod }}" required maxlength="{{ $tindakan ? 30 : 20 }}" class="input font-mono uppercase">
</div>
<div class="{{ $tindakan ? 'sm:col-span-3' : 'sm:col-span-4' }}">
    <label class="label">Nama <span class="text-red-600">*</span></label>
    <input name="nama" value="{{ $baru ? old('nama') : $r->nama }}" required maxlength="255" class="input">
</div>
@if ($tindakan)
    <div class="sm:col-span-1">
        <label class="label">Kumpulan</label>
        <select name="kumpulan" class="input" required>
            @foreach ($kumpulan as $k => $v)
                <option value="{{ $k }}" @selected(($baru ? old('kumpulan') : $r->kumpulan) === $k)>{{ $v }}</option>
            @endforeach
        </select>
    </div>
    <div class="sm:col-span-1">
        <label class="label">Susunan</label>
        <input name="susunan" type="number" min="0" value="{{ $baru ? old('susunan', 0) : $r->susunan }}" class="input">
    </div>
@endif
<div class="flex items-end pb-2 sm:col-span-1">
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="aktif" value="1" @checked($baru ? old('aktif', true) : $r->aktif) class="rounded border-slate-300 text-mbsp-700"> Aktif
    </label>
</div>
@if ($adaKeterangan)
    <div class="{{ $tindakan ? 'sm:col-span-4' : 'sm:col-span-6' }}">
        <label class="label">Keterangan</label>
        <input name="keterangan" value="{{ $baru ? old('keterangan') : $r->keterangan }}" maxlength="2000" class="input">
    </div>
@endif
@if ($tindakan)
    <div class="sm:col-span-2">
        <label class="label">Rujukan Peraturan</label>
        <input name="rujukan_peraturan" value="{{ $baru ? old('rujukan_peraturan') : $r->rujukan_peraturan }}" maxlength="255" class="input">
    </div>
@endif
