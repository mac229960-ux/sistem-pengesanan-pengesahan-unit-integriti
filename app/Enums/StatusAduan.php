<?php

namespace App\Enums;

enum StatusAduan: string
{
    case Baru = 'baru';
    case Diagihkan = 'diagihkan';
    case DalamSiasatan = 'dalam_siasatan';
    case MenungguKelulusan = 'menunggu_kelulusan';
    case Dikembalikan = 'dikembalikan';
    case Diluluskan = 'diluluskan';
    case Selesai = 'selesai';
    case TidakDiteruskan = 'tidak_diteruskan';

    public function label(): string
    {
        return match ($this) {
            self::Baru => 'Baru Diterima',
            self::Diagihkan => 'Diagihkan',
            self::DalamSiasatan => 'Dalam Siasatan',
            self::MenungguKelulusan => 'Menunggu Kelulusan',
            self::Dikembalikan => 'Dikembalikan untuk Pindaan',
            self::Diluluskan => 'Diluluskan – Menunggu Tindakan',
            self::Selesai => 'Selesai / Ditutup',
            self::TidakDiteruskan => 'Tidak Diteruskan',
        };
    }

    public function warna(): string
    {
        return match ($this) {
            self::Baru => 'bg-sky-100 text-sky-800',
            self::Diagihkan => 'bg-indigo-100 text-indigo-800',
            self::DalamSiasatan => 'bg-amber-100 text-amber-800',
            self::MenungguKelulusan => 'bg-purple-100 text-purple-800',
            self::Dikembalikan => 'bg-orange-100 text-orange-800',
            self::Diluluskan => 'bg-teal-100 text-teal-800',
            self::Selesai => 'bg-emerald-100 text-emerald-800',
            self::TidakDiteruskan => 'bg-slate-200 text-slate-700',
        };
    }

    /** Status di mana kes masih aktif dan dikira SLA. */
    public function aktif(): bool
    {
        return ! in_array($this, [self::Selesai, self::TidakDiteruskan], true);
    }

    public static function aktifValues(): array
    {
        return collect(self::cases())->filter->aktif()->map->value->values()->all();
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn ($c) => [$c->value => $c->label()])->all();
    }
}
