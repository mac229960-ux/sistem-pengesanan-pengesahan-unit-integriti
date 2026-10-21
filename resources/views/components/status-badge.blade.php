@props(['status'])
<span {{ $attributes->merge(['class' => 'badge '.$status->warna()]) }}>{{ $status->label() }}</span>
