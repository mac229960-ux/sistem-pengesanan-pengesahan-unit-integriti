<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($tajuk) ? $tajuk.' – ' : '' }}Sistem Integriti MBSP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-slate-100 font-sans text-slate-800 antialiased">
@php
    use App\Enums\Peranan;
    $u = auth()->user();
    $belumBaca = $u->unreadNotifications()->count();
    $menu = [
        ['Dashboard', 'dashboard', 'dashboard', null],
        ['Senarai Aduan', 'aduan.index', 'aduan.*', [Peranan::PembantuTadbir, Peranan::PegawaiSiasatan, Peranan::KetuaUnit]],
        ['Daftar Aduan Baru', 'aduan.create', 'aduan.create', [Peranan::PembantuTadbir, Peranan::KetuaUnit]],
        ['Kelulusan', 'kelulusan.index', 'kelulusan.*', [Peranan::KetuaUnit]],
        ['Pustaka Peraturan', 'rujukan.index', 'rujukan.*', [Peranan::KetuaUnit, Peranan::PegawaiSiasatan, Peranan::Pentadbir]],
        ['Laporan & Analisis', 'laporan.index', 'laporan.*', [Peranan::KetuaUnit, Peranan::Pengurusan, Peranan::PegawaiSiasatan]],
        ['Jejak Audit', 'admin.audit.index', 'admin.audit.*', [Peranan::Pentadbir, Peranan::KetuaUnit]],
    ];
    $menuAdmin = [
        ['Pengguna', 'admin.pengguna.index', 'admin.pengguna.*', []],
        ['Kategori Aduan', 'admin.data.index', null, ['jenis' => 'kategori']],
        ['Saluran Aduan', 'admin.data.index', null, ['jenis' => 'saluran']],
        ['Jabatan', 'admin.data.index', null, ['jenis' => 'jabatan']],
        ['Jenis Tindakan', 'admin.data.index', null, ['jenis' => 'tindakan']],
        ['Data HR (Mock)', 'admin.pegawai-hr.index', 'admin.pegawai-hr.*', []],
    ];
@endphp
<div x-data="{ buka: false }" class="min-h-screen lg:flex">
    {{-- Sidebar --}}
    <aside :class="buka ? 'translate-x-0' : '-translate-x-full'" class="fixed inset-y-0 left-0 z-40 w-64 transform bg-mbsp-950 text-slate-200 transition lg:static lg:translate-x-0 print:hidden">
        <div class="flex h-16 items-center gap-3 border-b border-white/10 px-5">
            <div class="flex h-9 w-9 items-center justify-center rounded-md bg-white/10 text-sm font-bold">UI</div>
            <div class="leading-tight">
                <div class="text-sm font-semibold text-white">Sistem Integriti</div>
                <div class="text-[11px] text-slate-400">Majlis Bandaraya Seberang Perai</div>
            </div>
        </div>
        <nav class="space-y-1 p-3 text-sm">
            @foreach ($menu as [$label, $route, $pola, $peranan])
                @continue($peranan && ! $u->ada(...$peranan))
                <a href="{{ route($route) }}" @class(['block rounded-md px-3 py-2 hover:bg-white/10', 'bg-white/15 text-white font-medium' => request()->routeIs($pola) && ! ($route === 'aduan.index' && request()->routeIs('aduan.create'))])>{{ $label }}</a>
            @endforeach
            @if ($u->isPentadbir())
                <div class="px-3 pt-4 pb-1 text-[11px] font-semibold uppercase tracking-wider text-slate-400">Pentadbiran</div>
                @foreach ($menuAdmin as [$label, $route, $pola, $param])
                    @php $aktif = $pola ? request()->routeIs($pola) : (request()->routeIs($route) && request()->route('jenis') === ($param['jenis'] ?? null)); @endphp
                    <a href="{{ route($route, $param) }}" @class(['block rounded-md px-3 py-2 hover:bg-white/10', 'bg-white/15 text-white font-medium' => $aktif])>{{ $label }}</a>
                @endforeach
            @endif
        </nav>
        <div class="absolute bottom-0 w-full border-t border-white/10 p-4 text-[11px] text-slate-400">
            Unit Integriti, Jabatan Khidmat Pengurusan<br>
            <span class="text-amber-300">SULIT</span> – Untuk kegunaan rasmi sahaja
        </div>
    </aside>
    <div x-show="buka" x-cloak @click="buka=false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-4 border-b border-slate-200 bg-white px-4 lg:px-8 print:hidden">
            <div class="flex items-center gap-3">
                <button @click="buka=!buka" class="rounded p-2 hover:bg-slate-100 lg:hidden" aria-label="Menu">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <h1 class="truncate text-lg font-semibold text-slate-800">{{ $tajuk ?? 'Dashboard' }}</h1>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('notifikasi.index') }}" class="relative rounded-full p-2 text-slate-500 hover:bg-slate-100" title="Notifikasi">
                    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2c0 .5-.2 1-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    @if ($belumBaca)
                        <span class="absolute -right-0.5 -top-0.5 flex h-5 min-w-5 items-center justify-center rounded-full bg-red-600 px-1 text-[10px] font-bold text-white">{{ $belumBaca > 99 ? '99+' : $belumBaca }}</span>
                    @endif
                </a>
                <div x-data="{ m: false }" class="relative">
                    <button @click="m=!m" @click.outside="m=false" class="flex items-center gap-2 rounded-md px-2 py-1 hover:bg-slate-100">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-mbsp-700 text-xs font-semibold text-white">{{ \Illuminate\Support\Str::of($u->name)->explode(' ')->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
                        <div class="hidden text-left text-xs leading-tight sm:block">
                            <div class="font-medium text-slate-800">{{ $u->name }}</div>
                            <div class="text-slate-500">{{ $u->peranan->label() }}</div>
                        </div>
                    </button>
                    <div x-show="m" x-cloak class="absolute right-0 mt-2 w-48 rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-slate-200">
                        <a href="{{ route('profil') }}" class="block px-4 py-2 hover:bg-slate-50">Profil & Kata Laluan</a>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="block w-full px-4 py-2 text-left text-red-600 hover:bg-slate-50">Log Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1 p-4 lg:p-8">
            @foreach (['berjaya' => 'bg-emerald-50 text-emerald-800 ring-emerald-200', 'ralat' => 'bg-red-50 text-red-800 ring-red-200', 'info' => 'bg-sky-50 text-sky-800 ring-sky-200'] as $k => $kelas)
                @if (session($k))
                    <div x-data="{ s: true }" x-show="s" class="mb-4 flex items-start justify-between gap-4 rounded-md px-4 py-3 text-sm ring-1 {{ $kelas }}">
                        <div>{{ session($k) }}</div>
                        <button @click="s=false" class="opacity-60 hover:opacity-100">✕</button>
                    </div>
                @endif
            @endforeach
            @if ($errors->any())
                <div class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-800 ring-1 ring-red-200">
                    <div class="font-medium">Sila semak maklumat yang dimasukkan:</div>
                    <ul class="mt-1 list-disc pl-5">
                        @foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                    </ul>
                </div>
            @endif

            {{ $slot }}
        </main>
        <footer class="border-t border-slate-200 bg-white px-8 py-3 text-xs text-slate-500 print:hidden">
            © {{ date('Y') }} Majlis Bandaraya Seberang Perai · Sistem Pengesanan dan Pengesahan Unit Integriti
        </footer>
    </div>
</div>
@stack('scripts')
</body>
</html>
