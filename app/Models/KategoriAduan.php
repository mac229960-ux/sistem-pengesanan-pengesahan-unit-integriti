<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KategoriAduan extends Model
{
    protected $table = 'kategori_aduan';

    protected $fillable = ['kod', 'nama', 'keterangan', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];
}
