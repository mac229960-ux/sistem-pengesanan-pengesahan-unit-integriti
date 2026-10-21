<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TindakanDiambil extends Model
{
    protected $table = 'tindakan_diambil';

    protected $fillable = ['aduan_id', 'jenis_tindakan_id', 'tarikh_tindakan', 'no_rujukan_surat', 'butiran', 'direkod_oleh'];

    protected $casts = ['tarikh_tindakan' => 'date'];

    public function jenisTindakan()
    {
        return $this->belongsTo(JenisTindakan::class);
    }

    public function perekod()
    {
        return $this->belongsTo(User::class, 'direkod_oleh');
    }
}
