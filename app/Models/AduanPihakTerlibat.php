<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AduanPihakTerlibat extends Model
{
    protected $table = 'aduan_pihak_terlibat';

    protected $fillable = ['aduan_id', 'pegawai_hr_id', 'nama', 'no_pekerja', 'jawatan', 'jabatan', 'peranan', 'catatan'];

    public const PERANAN = ['didakwa' => 'Pegawai Diadu', 'saksi' => 'Saksi', 'lain' => 'Lain-lain'];

    public function aduan()
    {
        return $this->belongsTo(Aduan::class);
    }

    public function pegawaiHr()
    {
        return $this->belongsTo(PegawaiHr::class);
    }
}
