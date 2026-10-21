<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisTindakan extends Model
{
    protected $table = 'jenis_tindakan';

    protected $fillable = ['kod', 'nama', 'kumpulan', 'keterangan', 'rujukan_peraturan', 'susunan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];

    public const KUMPULAN = [
        'tatatertib' => 'Tindakan Tatatertib',
        'pentadbiran' => 'Tindakan Pentadbiran',
        'rujukan_luar' => 'Rujukan kepada Agensi Luar',
        'penambahbaikan' => 'Penambahbaikan Sistem / Prosedur',
        'tiada' => 'Tiada Tindakan',
    ];
}
