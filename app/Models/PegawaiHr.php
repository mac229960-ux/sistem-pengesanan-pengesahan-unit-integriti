<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PegawaiHr extends Model
{
    protected $table = 'pegawai_hr';

    protected $fillable = [
        'no_pekerja', 'nama', 'no_kp', 'jawatan', 'gred', 'jabatan_id', 'emel', 'telefon',
        'tarikh_lantikan', 'status_perkhidmatan', 'disegerak_pada',
    ];

    protected $casts = ['tarikh_lantikan' => 'date', 'disegerak_pada' => 'datetime'];

    public function jabatan()
    {
        return $this->belongsTo(Jabatan::class);
    }

    public function pihakTerlibat()
    {
        return $this->hasMany(AduanPihakTerlibat::class);
    }
}
