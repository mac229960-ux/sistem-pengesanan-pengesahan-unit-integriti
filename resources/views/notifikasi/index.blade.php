<x-layouts.app tajuk="Notifikasi">
    <div class="card">
        <div class="card-header">
            <h2 class="card-title">Notifikasi Saya</h2>
            @if (auth()->user()->unreadNotifications()->exists())
                <form method="POST" action="{{ route('notifikasi.baca-semua') }}">@csrf
                    <button class="btn-secondary btn-sm">Tanda semua telah dibaca</button>
                </form>
            @endif
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($notifikasi as $n)
                <li @class(['flex items-start justify-between gap-4 px-5 py-3', 'bg-mbsp-50/60' => ! $n->read_at])>
                    <div class="min-w-0">
                        <div class="flex items-center gap-2">
                            @unless ($n->read_at)<span class="h-2 w-2 shrink-0 rounded-full bg-mbsp-600" title="Belum dibaca"></span>@endunless
                            <span class="font-medium text-slate-800">{{ $n->data['tajuk'] ?? 'Notifikasi' }}</span>
                            @isset($n->data['no_rujukan'])<span class="font-mono text-xs text-slate-500">{{ $n->data['no_rujukan'] }}</span>@endisset
                        </div>
                        <p class="mt-0.5 text-sm text-slate-600">{{ $n->data['mesej'] ?? '' }}</p>
                        <p class="mt-0.5 text-xs text-slate-400">{{ $n->created_at->format('d/m/Y H:i') }} · {{ $n->created_at->diffForHumans() }}</p>
                    </div>
                    @isset($n->data['aduan_id'])
                        <form method="POST" action="{{ route('notifikasi.baca', $n->id) }}" class="shrink-0">@csrf
                            <button class="btn-secondary btn-sm">Buka Kes</button>
                        </form>
                    @endisset
                </li>
            @empty
                <li class="px-5 py-8 text-center text-sm text-slate-500">Tiada notifikasi.</li>
            @endforelse
        </ul>
        @if ($notifikasi->hasPages())<div class="border-t border-slate-200 px-5 py-3">{{ $notifikasi->links() }}</div>@endif
    </div>
</x-layouts.app>
