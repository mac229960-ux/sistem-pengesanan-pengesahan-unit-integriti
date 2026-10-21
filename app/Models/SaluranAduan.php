<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaluranAduan extends Model
{
    protected $table = 'saluran_aduan';

    protected $fillable = ['kod', 'nama', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];
}
