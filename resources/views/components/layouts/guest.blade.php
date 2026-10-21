<!DOCTYPE html>
<html lang="ms">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $tajuk ?? 'Log Masuk' }} – Sistem Integriti MBSP</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-gradient-to-br from-mbsp-950 via-mbsp-900 to-mbsp-700 font-sans text-slate-800 antialiased">
<div class="flex min-h-screen items-center justify-center p-4">
    <div class="w-full max-w-md">
        <div class="mb-6 text-center text-white">
            <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-xl bg-white/10 text-lg font-bold ring-1 ring-white/20">UI</div>
            <h1 class="text-xl font-semibold">Sistem Pengesanan dan Pengesahan</h1>
            <p class="text-sm text-slate-300">Unit Integriti · Majlis Bandaraya Seberang Perai</p>
        </div>
        <div class="rounded-xl bg-white p-6 shadow-xl sm:p-8">
            {{ $slot }}
        </div>
        <p class="mt-6 text-center text-xs text-slate-300">Sistem ini mengandungi maklumat SULIT. Akses tanpa kebenaran adalah satu kesalahan.</p>
    </div>
</div>
</body>
</html>
