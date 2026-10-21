@props(['aduan'])
@php
    $tahap = $aduan->tahapSla();
    $baki = $aduan->bakiHari();
    [$kelas, $teks] = match ($tahap) {
        'lewat' => ['bg-red-600 text-white', 'Lewat '.abs($baki).' hari'],
        'merah' => ['bg-red-100 text-red-800', $baki.' hari lagi'],
        'kuning' => ['bg-amber-100 text-amber-800', $baki.' hari lagi'],
        'hijau' => ['bg-emerald-100 text-emerald-800', $baki.' hari lagi'],
        default => $aduan->selesaiDalamSla() === false
            ? ['bg-slate-200 text-slate-700', 'Selesai (lewat SLA)']
            : ['bg-slate-100 text-slate-600', 'Selesai'],
    };
@endphp
<span {{ $attributes->merge(['class' => 'badge '.$kelas]) }} title="Tarikh akhir SLA: {{ $aduan->tarikh_akhir_sla->format('d/m/Y') }}">{{ $teks }}</span>
