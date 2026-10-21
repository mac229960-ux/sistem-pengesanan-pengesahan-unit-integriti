@props(['label', 'nama', 'wajib' => false, 'nota' => null])
<div {{ $attributes->only('class') }}>
    <label for="{{ $nama }}" class="label">{{ $label }} @if($wajib)<span class="text-red-600">*</span>@endif</label>
    {{ $slot }}
    @if ($nota)<p class="mt-1 text-xs text-slate-500">{{ $nota }}</p>@endif
    @error($nama)<p class="error">{{ $message }}</p>@enderror
</div>
