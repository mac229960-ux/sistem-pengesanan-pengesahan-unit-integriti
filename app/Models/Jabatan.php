<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jabatan extends Model
{
    protected $table = 'jabatan';

    protected $fillable = ['kod', 'nama', 'aktif'];

    protected $casts = ['aktif' => 'boolean'];
}
