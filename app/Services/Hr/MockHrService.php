<?php

namespace App\Services\Hr;

use App\Models\PegawaiHr;
use Illuminate\Support\Collection;

class MockHrService implements HrService
{
    public function cari(string $kata, int $had = 20): Collection
    {
        return PegawaiHr::with('jabatan')
            ->where(fn ($q) => $q->where('nama', 'like', "%{$kata}%")
                ->orWhere('no_pekerja', 'like', "%{$kata}%")
                ->orWhere('no_kp', 'like', "%{$kata}%"))
            ->orderBy('nama')->limit($had)->get()
            ->map(fn (PegawaiHr $p) => $this->format($p));
    }

    public function butiran(string $noPekerja): ?array
    {
        $p = PegawaiHr::with('jabatan')->where('no_pekerja', $noPekerja)->first();

        return $p ? $this->format($p) : null;
    }

    private function format(PegawaiHr $p): array
    {
        return [
            'id' => $p->id,
            'no_pekerja' => $p->no_pekerja,
            'nama' => $p->nama,
            'jawatan' => $p->jawatan,
            'gred' => $p->gred,
            'jabatan' => $p->jabatan?->nama,
            'emel' => $p->emel,
            'status' => $p->status_perkhidmatan,
        ];
    }
}
