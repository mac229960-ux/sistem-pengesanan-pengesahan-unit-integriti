{{-- Jadual agregat ringkas: $tajuk, $lajur, $data (label => bilangan) --}}
@php $jumlah = array_sum($data); @endphp
<div class="card">
    <div class="card-header"><h3 class="card-title">{{ $tajuk }}</h3></div>
    <table class="table">
        <thead><tr><th>{{ $lajur }}</th><th class="text-right">Bilangan</th><th class="w-1/3">%</th></tr></thead>
        <tbody>
        @forelse ($data as $label => $n)
            <tr>
                <td>{{ $label }}</td>
                <td class="text-right font-medium">{{ $n }}</td>
                <td>
                    @php $pct = $jumlah ? round($n / $jumlah * 100) : 0; @endphp
                    <div class="flex items-center gap-2">
                        <div class="h-2 flex-1 rounded bg-slate-100"><div class="h-2 rounded bg-mbsp-500" style="width: {{ $pct }}%"></div></div>
                        <span class="w-9 text-right text-xs text-slate-500">{{ $pct }}%</span>
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="3" class="py-4 text-center text-slate-500">Tiada data.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
