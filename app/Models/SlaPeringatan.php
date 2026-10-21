<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPeringatan extends Model
{
    public $timestamps = false;

    protected $table = 'sla_peringatan';

    protected $fillable = ['aduan_id', 'jenis', 'dihantar_pada'];

    protected $casts = ['dihantar_pada' => 'datetime'];
}
