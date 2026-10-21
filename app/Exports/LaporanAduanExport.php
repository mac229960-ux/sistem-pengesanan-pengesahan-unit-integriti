<?php

namespace App\Exports;

use App\Services\StatistikAduan;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

/** Eksport laporan aduan. Tiada data peribadi pengadu dimasukkan. */
class LaporanAduanExport implements WithMultipleSheets
{
    public function __construct(
        private StatistikAduan $stat,
        private array $penapis,
        private bool $termasukKes,
    ) {}

    public function sheets(): array
    {
        $r = $this->stat->ringkasan();

        $sheets = [
            new JadualSheet('Ringkasan', ['Perkara', 'Nilai'], [
                ['Jumlah aduan', $r['jumlah']],
                ['Aktif', $r['aktif']],
                ['Baru', $r['baru']],
                ['Dalam siasatan', $r['dalam_siasatan']],
                ['Menunggu kelulusan', $r['menunggu_kelulusan']],
                ['Hampir tamat SLA (≤30 hari)', $r['hampir_sla']],
                ['Lewat SLA', $r['lewat']],
                ['Ditutup', $r['ditutup']],
                ['Ditutup dalam SLA', $r['ditutup_dalam_sla']],
                ['% selesai dalam SLA', $r['peratus_dalam_sla'] ?? '-'],
                ['Tempoh (dari)', $this->penapis['dari'] ?? 'Semua'],
                ['Tempoh (hingga)', $this->penapis['hingga'] ?? 'Semua'],
                ['Dijana pada', now()->format('d/m/Y H:i')],
                ['Pengelasan', 'SULIT'],
            ]),
            $this->agregat('Ikut Status', 'Status', $this->stat->ikutStatus()),
            $this->agregat('Ikut Kategori', 'Kategori', $this->stat->ikutKategori()),
            $this->agregat('Ikut Saluran', 'Saluran', $this->stat->ikutSaluran()),
            $this->agregat('Ikut Jabatan', 'Jabatan', $this->stat->ikutJabatan()),
            $this->agregat('Ikut Keputusan', 'Keputusan Siasatan', $this->stat->ikutKeputusan()),
            new JadualSheet('Ikut Pegawai', ['Pegawai Siasatan', 'Jumlah', 'Aktif', 'Lewat SLA', 'Ditutup', 'Ditutup dalam SLA', '% dalam SLA'],
                collect($this->stat->ikutPegawai())->map(fn ($p) => [
                    $p['nama'], $p['jumlah'], $p['aktif'], $p['lewat'], $p['ditutup'], $p['dalam_sla'], $p['peratus_sla'] ?? '-',
                ])->all()),
        ];

        if ($this->termasukKes) {
            $baris = $this->stat->query()->with(['kategori', 'saluran', 'jabatanTerlibat', 'pegawaiSiasatan'])
                ->orderByDesc('tarikh_terima')->get()
                ->map(fn ($a) => [
                    $a->no_rujukan,
                    $a->tarikh_terima->format('d/m/Y'),
                    $a->kategori?->nama,
                    $a->saluran?->nama,
                    $a->jabatanTerlibat?->nama,
                    $a->tajuk,
                    $a->status->label(),
                    $a->keputusan_siasatan?->label(),
                    $a->pegawaiSiasatan?->name,
                    $a->tarikh_akhir_sla->format('d/m/Y'),
                    $a->bakiHari(),
                    $a->tarikh_selesai?->format('d/m/Y'),
                ])->all();

            $sheets[] = new JadualSheet('Senarai Kes', [
                'No. Rujukan', 'Tarikh Terima', 'Kategori', 'Saluran', 'Jabatan Terlibat', 'Tajuk', 'Status',
                'Keputusan Siasatan', 'Pegawai Siasatan', 'Tarikh Akhir SLA', 'Baki Hari SLA', 'Tarikh Selesai',
            ], $baris);
        }

        return $sheets;
    }

    private function agregat(string $tajuk, string $lajur, array $data): JadualSheet
    {
        return new JadualSheet($tajuk, [$lajur, 'Bilangan'], collect($data)->map(fn ($n, $k) => [$k, $n])->values()->all());
    }
}
