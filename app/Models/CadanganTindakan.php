<?php

namespace App\Models;

use App\Enums\KeputusanSiasatan;
use Illuminate\Database\Eloquent\Model;

class CadanganTindakan extends Model
{
    protected $table = 'cadangan_tindakan';

    protected $fillable = [
        'aduan_id', 'versi', 'disediakan_oleh', 'keputusan_siasatan', 'dapatan', 'justifikasi',
        'peruntukan_peraturan', 'ai_cadangan_id', 'status', 'dihantar_pada',
    ];

    protected $casts = [
        'keputusan_siasatan' => KeputusanSiasatan::class,
        'dihantar_pada' => 'datetime',
    ];

    public const STATUS = [
        'draf' => 'Draf',
        'dihantar' => 'Dihantar untuk Kelulusan',
        'diluluskan' => 'Diluluskan',
        'dikembalikan' => 'Dikembalikan',
    ];

    public function aduan()
    {
        return $this->belongsTo(Aduan::class);
    }

    public function penyedia()
    {
        return $this->belongsTo(User::class, 'disediakan_oleh');
    }

    public function jenisTindakan()
    {
        return $this->belongsToMany(JenisTindakan::class, 'cadangan_tindakan_jenis');
    }

    public function kelulusan()
    {
        return $this->hasOne(Kelulusan::class)->latestOfMany();
    }

    public function aiCadangan()
    {
        return $this->belongsTo(AiCadangan::class);
    }
}
