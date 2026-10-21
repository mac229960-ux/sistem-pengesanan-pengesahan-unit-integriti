<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<title>Laporan Aduan Integriti</title>
<style>
    @page { margin: 60px 30px 45px 30px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
    header { position: fixed; top: -45px; left: 0; right: 0; text-align: center; font-weight: bold; color: #b91c1c; font-size: 11px; }
    footer { position: fixed; bottom: -30px; left: 0; right: 0; text-align: center; font-size: 8px; color: #64748b; }
    footer .sulit { color: #b91c1c; font-weight: bold; }
    h1 { font-size: 15px; margin: 0 0 2px; }
    h2 { font-size: 11px; margin: 14px 0 5px; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; }
    .sub { color: #475569; margin-bottom: 8px; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    th, td { border: 1px solid #cbd5e1; padding: 3px 5px; text-align: left; vertical-align: top; }
    th { background: #e2e8f0; }
    .r { text-align: right; }
    .kotak td { width: 16.6%; text-align: center; }
    .kotak .n { font-size: 14px; font-weight: bold; }
    .dua { width: 100%; }
    .dua td.c { width: 50%; border: none; padding: 0 6px 0 0; }
    .merah { color: #b91c1c; font-weight: bold; }
</style>
</head>
<body>
<header>SULIT</header>
<footer><span class="sulit">SULIT</span> · Dijana oleh {{ $user->name }} pada {{ now()->format('d/m/Y H:i') }} · Sistem Pengesanan dan Pengesahan Unit Integriti MBSP</footer>

<h1>Laporan Aduan Integriti</h1>
<div class="sub">Unit Integriti, Jabatan Khidmat Pengurusan · Majlis Bandaraya Seberang Perai</div>
<div class="sub">
    Penapis:
    @forelse ($penapis as $k => $v) <strong>{{ $k }}:</strong> {{ $v }}@if (! $loop->last); @endif @empty Semua rekod @endforelse
</div>

<table class="kotak">
    <tr>
        <td><div class="n">{{ $ringkasan['jumlah'] }}</div>Jumlah Aduan</td>
        <td><div class="n">{{ $ringkasan['aktif'] }}</div>Aktif</td>
        <td><div class="n merah">{{ $ringkasan['lewat'] }}</div>Lewat SLA</td>
        <td><div class="n">{{ $ringkasan['hampir_sla'] }}</div>Hampir Tamat SLA</td>
        <td><div class="n">{{ $ringkasan['ditutup'] }}</div>Ditutup</td>
        <td><div class="n">{{ $ringkasan['peratus_dalam_sla'] === null ? '–' : $ringkasan['peratus_dalam_sla'].'%' }}</div>Selesai dalam SLA {{ config('integriti.sla_bulan') }} bulan</td>
    </tr>
</table>

@php
    $jadual = [
        'Mengikut Status' => $ikutStatus, 'Mengikut Kategori' => $ikutKategori, 'Mengikut Saluran' => $ikutSaluran,
        'Mengikut Jabatan Terlibat' => $ikutJabatan, 'Keputusan Siasatan' => $ikutKeputusan,
    ];
@endphp
<table class="dua">
    @foreach (array_chunk($jadual, 2, true) as $pasangan)
        <tr>
            @foreach ($pasangan as $tajuk => $data)
                <td class="c">
                    <h2>{{ $tajuk }}</h2>
                    <table>
                        <tr><th>Perkara</th><th class="r">Bilangan</th></tr>
                        @foreach ($data as $label => $n)<tr><td>{{ $label }}</td><td class="r">{{ $n }}</td></tr>@endforeach
                    </table>
                </td>
            @endforeach
        </tr>
    @endforeach
</table>

@if ($ikutPegawai)
    <h2>Beban Kerja & Pematuhan SLA Pegawai Siasatan</h2>
    <table>
        <tr><th>Pegawai Siasatan</th><th class="r">Jumlah</th><th class="r">Aktif</th><th class="r">Lewat SLA</th><th class="r">Ditutup</th><th class="r">% dalam SLA</th></tr>
        @foreach ($ikutPegawai as $p)
            <tr><td>{{ $p['nama'] }}</td><td class="r">{{ $p['jumlah'] }}</td><td class="r">{{ $p['aktif'] }}</td><td class="r">{{ $p['lewat'] }}</td><td class="r">{{ $p['ditutup'] }}</td><td class="r">{{ $p['peratus_sla'] === null ? '–' : $p['peratus_sla'].'%' }}</td></tr>
        @endforeach
    </table>
@endif

@if ($kes !== null)
    <h2>Senarai Kes ({{ $kes->count() }})</h2>
    <table>
        <tr><th>No. Rujukan</th><th>Tarikh Terima</th><th>Kategori</th><th>Tajuk</th><th>Pegawai Siasatan</th><th>Status</th><th>Keputusan</th><th>Tarikh Akhir SLA</th><th class="r">Baki Hari</th></tr>
        @foreach ($kes as $a)
            @php $baki = $a->bakiHari(); @endphp
            <tr>
                <td>{{ $a->no_rujukan }}</td>
                <td>{{ $a->tarikh_terima->format('d/m/Y') }}</td>
                <td>{{ $a->kategori?->nama }}</td>
                <td>{{ \Illuminate\Support\Str::limit($a->tajuk, 70) }}</td>
                <td>{{ $a->pegawaiSiasatan?->name ?? '–' }}</td>
                <td>{{ $a->status->label() }}</td>
                <td>{{ $a->keputusan_siasatan?->label() ?? '–' }}</td>
                <td>{{ $a->tarikh_akhir_sla->format('d/m/Y') }}</td>
                <td class="r {{ $baki !== null && $baki < 0 ? 'merah' : '' }}">{{ $baki ?? 'Selesai' }}</td>
            </tr>
        @endforeach
    </table>
@endif
</body>
</html>
