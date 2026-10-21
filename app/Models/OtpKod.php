<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OtpKod extends Model
{
    protected $table = 'otp_kod';

    protected $fillable = ['user_id', 'kod_hash', 'tamat_pada', 'cubaan', 'digunakan_pada', 'ip'];

    protected $casts = ['tamat_pada' => 'datetime', 'digunakan_pada' => 'datetime'];
}
