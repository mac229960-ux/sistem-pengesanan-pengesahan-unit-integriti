@props(['label', 'nilai', 'warna' => 'text-slate-900', 'nota' => null, 'href' => null])
@php $isi = '<div class="text-xs font-medium uppercase tracking-wide text-slate-500">'.e($label).'</div><div class="mt-1 text-2xl font-bold '.$warna.'">'.e($nilai).'</div>'.($nota ? '<div class="mt-0.5 text-xs text-slate-500">'.e($nota).'</div>' : ''); @endphp
@if ($href)
    <a href="{{ $href }}" class="card block p-4 hover:ring-mbsp-300">{!! $isi !!}</a>
@else
    <div class="card p-4">{!! $isi !!}</div>
@endif
