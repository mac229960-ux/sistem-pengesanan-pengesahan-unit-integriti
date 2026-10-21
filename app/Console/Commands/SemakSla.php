<?php

namespace App\Console\Commands;

use App\Models\Aduan;
use App\Models\SlaPeringatan;
use App\Services\Notifikasi;
use Illuminate\Console\Command;

/**
 * Peringatan & eskalasi SLA 6 bulan (BRS-014, BRS-022). Dijadualkan setiap hari.
 */
class SemakSla extends Command
{
    protected $signature = 'integriti:semak-sla';

    protected $description = 'Hantar peringatan SLA bagi aduan yang hampir tamat tempoh atau telah lewat';

    public function handle(): int
    {
        $ambang = config('integriti.peringatan_baki_hari');
        $selang = config('integriti.peringatan_lewat_selang_hari');
        $ketua = Notifikasi::ketuaUnit();
        $dihantar = 0;

        Aduan::aktif()->with('pegawaiSiasatan')->chunkById(200, function ($senarai) use ($ambang, $selang, $ketua, &$dihantar) {
            foreach ($senarai as $aduan) {
                $baki = $aduan->bakiHari();

                if ($baki < 0) {
                    $terakhir = SlaPeringatan::where('aduan_id', $aduan->id)->where('jenis', 'lewat')->max('dihantar_pada');
                    if ($terakhir && now()->diffInDays($terakhir, true) < $selang) {
                        continue;
                    }
                    $jenis = 'lewat';
                    $tajuk = 'ESKALASI: Kes melebihi tempoh SLA';
                    $mesej = "Kes {$aduan->no_rujukan} telah melebihi tempoh penyelesaian 6 bulan sebanyak ".abs($baki).' hari. Tindakan segera diperlukan.';
                    $penerima = $ketua->toBase()->concat([$aduan->pegawaiSiasatan]);
                } else {
                    // Ambang paling kecil yang telah dicapai dan belum dihantar.
                    $capai = collect($ambang)->sort()->first(fn ($h) => $baki <= $h);
                    if ($capai === null || SlaPeringatan::where('aduan_id', $aduan->id)->where('jenis', "baki_{$capai}")->exists()) {
                        continue;
                    }
                    $jenis = "baki_{$capai}";
                    $tajuk = "Peringatan SLA: {$baki} hari lagi";
                    $mesej = "Kes {$aduan->no_rujukan} perlu diselesaikan sebelum {$aduan->tarikh_akhir_sla->format('d/m/Y')} ({$baki} hari lagi).";
                    // Pegawai Siasatan dimaklumkan; Ketua Unit juga jika ≤ 30 hari atau belum diagihkan.
                    $penerima = collect([$aduan->pegawaiSiasatan]);
                    if ($capai <= 30 || ! $aduan->pegawaiSiasatan) {
                        $penerima = $penerima->concat($ketua);
                    }
                }

                Notifikasi::hantar($penerima->filter(), $aduan, 'sla', $tajuk, $mesej);
                SlaPeringatan::create(['aduan_id' => $aduan->id, 'jenis' => $jenis, 'dihantar_pada' => now()]);
                $dihantar++;
            }
        });

        $this->info("{$dihantar} peringatan SLA dihantar.");

        return self::SUCCESS;
    }
}
