<?php

namespace App\Models;

use App\Enums\StatusAduan;
use Illuminate\Database\Eloquent\Model;

class StatusSejarah extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'status_sejarah';

    protected $fillable = ['aduan_id', 'status_dari', 'status_ke', 'user_id', 'catatan'];

    protected $casts = ['status_dari' => StatusAduan::class, 'status_ke' => StatusAduan::class];

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
