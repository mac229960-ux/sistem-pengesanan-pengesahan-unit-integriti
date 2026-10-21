<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Kelulusan extends Model
{
    protected $table = 'kelulusan';

    protected $fillable = ['cadangan_tindakan_id', 'aduan_id', 'pelulus_id', 'keputusan', 'ulasan'];

    public function pelulus()
    {
        return $this->belongsTo(User::class, 'pelulus_id');
    }

    public function cadangan()
    {
        return $this->belongsTo(CadanganTindakan::class, 'cadangan_tindakan_id');
    }
}
