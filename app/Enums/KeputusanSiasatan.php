<?php

namespace App\Enums;

enum KeputusanSiasatan: string
{
    case Berasas = 'berasas';
    case TidakBerasas = 'tidak_berasas';
    case TiadaBukti = 'tiada_bukti';

    public function label(): string
    {
        return match ($this) {
            self::Berasas => 'Berasas (Bukti Mencukupi)',
            self::TidakBerasas => 'Tidak Berasas',
            self::TiadaBukti => 'Tiada Bukti / Tidak Dapat Dikesan',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
