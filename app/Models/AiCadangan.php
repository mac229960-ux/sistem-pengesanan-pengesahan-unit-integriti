<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiCadangan extends Model
{
    protected $table = 'ai_cadangan';

    protected $fillable = [
        'aduan_id', 'user_id', 'jenis', 'penyedia', 'model', 'prompt', 'jawapan', 'rujukan_dipetik', 'metadata',
        'status_semakan', 'ulasan_pegawai', 'disemak_oleh', 'disemak_pada', 'ralat', 'tempoh_ms',
    ];

    protected $casts = [
        'rujukan_dipetik' => 'array',
        'metadata' => 'array',
        'disemak_pada' => 'datetime',
    ];

    public const STATUS = [
        'belum_disemak' => 'Belum Disemak',
        'diterima' => 'Diterima',
        'diterima_sebahagian' => 'Diterima Sebahagian',
        'ditolak' => 'Ditolak',
    ];

    public function aduan()
    {
        return $this->belongsTo(Aduan::class);
    }

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function penyemak()
    {
        return $this->belongsTo(User::class, 'disemak_oleh');
    }
}
