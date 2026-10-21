<?php

namespace App\Services\Hr;

use App\Models\Jabatan;
use App\Models\PegawaiHr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

/**
 * Rangka penyambung API Sistem HR Bersepadu. Laluan dan nama medan perlu
 * disesuaikan apabila spesifikasi API sebenar diperoleh daripada ICT.
 * Setiap rekod yang diperoleh dicerminkan ke jadual pegawai_hr.
 */
class ApiHrService implements HrService
{
    public function cari(string $kata, int $had = 20): Collection
    {
        $res = $this->http()->get('/pegawai', ['q' => $kata, 'limit' => $had]);
        $res->throw();

        return collect($res->json('data', []))->map(fn ($r) => $this->cermin($r));
    }

    public function butiran(string $noPekerja): ?array
    {
        $res = $this->http()->get('/pegawai/'.urlencode($noPekerja));
        if ($res->status() === 404) {
            return null;
        }
        $res->throw();

        return $this->cermin($res->json('data'));
    }

    private function http()
    {
        return Http::baseUrl(config('integriti.hr.api_url'))
            ->withToken(config('integriti.hr.api_token'))
            ->acceptJson()->timeout(15);
    }

    private function cermin(array $r): array
    {
        $jabatan = isset($r['jabatan_kod'])
            ? Jabatan::firstOrCreate(['kod' => $r['jabatan_kod']], ['nama' => $r['jabatan'] ?? $r['jabatan_kod']])
            : null;

        $p = PegawaiHr::updateOrCreate(['no_pekerja' => $r['no_pekerja']], [
            'nama' => $r['nama'],
            'jawatan' => $r['jawatan'] ?? null,
            'gred' => $r['gred'] ?? null,
            'jabatan_id' => $jabatan?->id,
            'emel' => $r['emel'] ?? null,
            'status_perkhidmatan' => $r['status'] ?? 'aktif',
            'disegerak_pada' => now(),
        ]);

        return [
            'id' => $p->id, 'no_pekerja' => $p->no_pekerja, 'nama' => $p->nama, 'jawatan' => $p->jawatan,
            'gred' => $p->gred, 'jabatan' => $jabatan?->nama, 'emel' => $p->emel, 'status' => $p->status_perkhidmatan,
        ];
    }
}
