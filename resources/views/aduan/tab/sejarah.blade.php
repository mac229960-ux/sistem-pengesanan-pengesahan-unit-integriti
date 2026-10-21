<div class="card">
    <div class="card-header"><div class="card-title">Sejarah Status Kes</div></div>
    <div class="card-body">
        <ol class="relative border-l border-slate-200">
            @foreach ($aduan->sejarahStatus as $s)
                <li class="mb-6 ml-4">
                    <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-mbsp-600"></div>
                    <time class="text-xs text-slate-500">{{ $s->created_at->format('d/m/Y H:i') }} · {{ $s->pengguna?->name ?? 'Sistem' }}</time>
                    <div class="mt-1 flex flex-wrap items-center gap-2 text-sm">
                        @if ($s->status_dari)<x-status-badge :status="$s->status_dari" /> →@endif
                        <x-status-badge :status="$s->status_ke" />
                    </div>
                    @if ($s->catatan)<p class="mt-1 text-sm text-slate-600">{{ $s->catatan }}</p>@endif
                </li>
            @endforeach
        </ol>
    </div>
</div>
