<!DOCTYPE html>
<html lang="ms">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
    .sulit { text-align: center; font-weight: bold; color: #b91c1c; letter-spacing: 2px; }
    h1 { font-size: 15px; margin: 6px 0 2px; } h2 { font-size: 12px; background: #e2e8f0; padding: 4px 6px; margin: 14px 0 6px; }
    table { width: 100%; border-collapse: collapse; } td, th { border: 1px solid #cbd5e1; padding: 4px; vertical-align: top; text-align: left; }
    th { background: #f1f5f9; width: 28%; } .grid th { width: auto; } .kecil { color: #64748b; font-size: 9px; }
    @page { margin: 40px 36px; }
    footer { position: fixed; bottom: -24px; left: 0; right: 0; text-align: center; font-size: 8px; color: #64748b; }
</style>
</head>
<body>
<div class="sulit">SULIT</div>
<footer>SULIT · Dicetak oleh {{ $pencetak->name }} pada {{ now()->format('d/m/Y H:i') }} · Sistem Integriti MBSP</footer>
<h1>Ringkasan Kes {{ $aduan->no_rujukan }}</h1>
<div class="kecil">Unit Integriti, Jabatan Khidmat Pengurusan, Majlis Bandaraya Seberang Perai</div>

<h2>A. Maklumat Aduan</h2>
<table>
    <tr><th>Tajuk</th><td>{{ $aduan->tajuk }}</td></tr>
    <tr><th>Kategori / Saluran</th><td>{{ $aduan->kategori->nama }} / {{ $aduan->saluran->nama }}</td></tr>
    <tr><th>Tarikh Terima / Akhir SLA</th><td>{{ $aduan->tarikh_terima->format('d/m/Y') }} / {{ $aduan->tarikh_akhir_sla->format('d/m/Y') }}</td></tr>
    <tr><th>Status</th><td>{{ $aduan->status->label() }}{{ $aduan->keputusan_siasatan ? ' — '.$aduan->keputusan_siasatan->label() : '' }}</td></tr>
    <tr><th>Pengadu</th><td>{{ $aduan->namaPengaduPaparan() }}</td></tr>
    <tr><th>Lokasi</th><td>{{ $aduan->lokasi }}</td></tr>
    <tr><th>Jabatan Terlibat</th><td>{{ $aduan->jabatanTerlibat?->nama ?? '—' }}</td></tr>
    <tr><th>Pegawai Siasatan</th><td>{{ $aduan->pegawaiSiasatan?->name ?? '—' }}</td></tr>
    <tr><th>Keterangan</th><td>{!! nl2br(e($aduan->keterangan)) !!}</td></tr>
</table>

<h2>B. Pihak Terlibat</h2>
<table class="grid">
    <tr><th>Nama</th><th>No. Pekerja</th><th>Jawatan</th><th>Jabatan</th><th>Peranan</th></tr>
    @foreach ($aduan->pihakTerlibat as $p)
        <tr><td>{{ $p->nama }}</td><td>{{ $p->no_pekerja }}</td><td>{{ $p->jawatan }}</td><td>{{ $p->jabatan }}</td><td>{{ \App\Models\AduanPihakTerlibat::PERANAN[$p->peranan] ?? $p->peranan }}</td></tr>
    @endforeach
</table>

<h2>C. Aktiviti Siasatan</h2>
<table class="grid">
    <tr><th>Tarikh</th><th>Jenis</th><th>Tajuk / Butiran</th><th>Pegawai</th></tr>
    @forelse ($aduan->aktiviti as $a)
        <tr><td>{{ $a->tarikh_aktiviti->format('d/m/Y') }}</td><td>{{ \App\Models\SiasatanAktiviti::JENIS[$a->jenis] ?? $a->jenis }}</td><td><strong>{{ $a->tajuk }}</strong><br>{!! nl2br(e($a->butiran)) !!}</td><td>{{ $a->pegawai->name }}</td></tr>
    @empty
        <tr><td colspan="4">Tiada.</td></tr>
    @endforelse
</table>

<h2>D. Bukti</h2>
<table class="grid">
    <tr><th>No.</th><th>Jenis</th><th>Tajuk</th><th>Status</th><th>SHA-256</th></tr>
    @forelse ($aduan->bukti as $b)
        <tr><td>{{ $b->no_bukti }}</td><td>{{ \App\Models\Bukti::JENIS[$b->jenis] }}</td><td>{{ $b->tajuk }}</td><td>{{ \App\Models\Bukti::STATUS[$b->status_pengesahan] }}</td><td class="kecil">{{ $b->sha256 ? substr($b->sha256, 0, 24).'…' : '—' }}</td></tr>
    @empty
        <tr><td colspan="5">Tiada.</td></tr>
    @endforelse
</table>

@php $c = $aduan->cadanganTindakan->first(); @endphp
<h2>E. Dapatan & Cadangan Tindakan</h2>
@if ($c)
    <table>
        <tr><th>Versi / Status</th><td>v{{ $c->versi }} — {{ \App\Models\CadanganTindakan::STATUS[$c->status] }}</td></tr>
        <tr><th>Keputusan Siasatan</th><td>{{ $c->keputusan_siasatan->label() }}</td></tr>
        <tr><th>Dapatan</th><td>{!! nl2br(e($c->dapatan)) !!}</td></tr>
        <tr><th>Justifikasi</th><td>{!! nl2br(e($c->justifikasi)) !!}</td></tr>
        <tr><th>Peruntukan</th><td>{{ $c->peruntukan_peraturan ?? '—' }}</td></tr>
        <tr><th>Tindakan Dicadangkan</th><td>{{ $c->jenisTindakan->pluck('nama')->implode('; ') }}</td></tr>
        <tr><th>Disediakan Oleh</th><td>{{ $c->penyedia->name }}</td></tr>
    </table>
@else
    <p>Belum disediakan.</p>
@endif

<h2>F. Kelulusan & Tindakan Diambil</h2>
<table class="grid">
    <tr><th>Tarikh</th><th>Perkara</th><th>Oleh</th></tr>
    @foreach ($aduan->kelulusan as $k)
        <tr><td>{{ $k->created_at->format('d/m/Y') }}</td><td>{{ $k->keputusan === 'lulus' ? 'Diluluskan' : 'Dikembalikan' }}{{ $k->ulasan ? ': '.$k->ulasan : '' }}</td><td>{{ $k->pelulus->name }}</td></tr>
    @endforeach
    @foreach ($aduan->tindakanDiambil as $t)
        <tr><td>{{ $t->tarikh_tindakan->format('d/m/Y') }}</td><td>Tindakan: {{ $t->jenisTindakan->nama }}{{ $t->no_rujukan_surat ? ' ('.$t->no_rujukan_surat.')' : '' }} — {{ $t->butiran }}</td><td>—</td></tr>
    @endforeach
</table>
</body>
</html>
