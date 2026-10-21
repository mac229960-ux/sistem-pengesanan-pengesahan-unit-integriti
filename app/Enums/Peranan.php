<?php

namespace App\Enums;

enum Peranan: string
{
    case Pentadbir = 'pentadbir';
    case PembantuTadbir = 'pembantu_tadbir';
    case PegawaiSiasatan = 'pegawai_siasatan';
    case KetuaUnit = 'ketua_unit';
    case Pengurusan = 'pengurusan';

    public function label(): string
    {
        return match ($this) {
            self::Pentadbir => 'Pentadbir Sistem',
            self::PembantuTadbir => 'Pembantu Tadbir (Penerima Aduan)',
            self::PegawaiSiasatan => 'Pegawai Siasatan',
            self::KetuaUnit => 'Ketua Unit Integriti (Pelulus)',
            self::Pengurusan => 'Pengurusan (Paparan)',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
