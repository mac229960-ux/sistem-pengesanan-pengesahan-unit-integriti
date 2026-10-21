<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'audit_log';

    protected $fillable = [
        'user_id', 'tindakan', 'model_type', 'model_id', 'aduan_id', 'keterangan',
        'nilai_lama', 'nilai_baru', 'ip', 'user_agent', 'url',
    ];

    protected $casts = ['nilai_lama' => 'array', 'nilai_baru' => 'array'];

    public function pengguna()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function aduan()
    {
        return $this->belongsTo(Aduan::class);
    }
}
