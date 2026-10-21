<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Bukti extends Model
{
    use SoftDeletes;

    protected $table = 'bukti';

    protected $fillable = [
        'aduan_id', 'no_bukti', 'jenis', 'tajuk', 'keterangan', 'sumber', 'tarikh_diperoleh',
        'nama_fail_asal', 'laluan_fail', 'mime', 'saiz', 'sha256',
        'status_pengesahan', 'catatan_pengesahan', 'disahkan_oleh', 'tarikh_disahkan', 'dimuatnaik_oleh',
    ];

    protected $casts = ['tarikh_diperoleh' => 'date', 'tarikh_disahkan' => 'datetime'];

    public const JENIS = [
        'dokumen' => 'Dokumen',
        'gambar' => 'Gambar',
        'audio' => 'Rakaman Audio',
        'video' => 'Rakaman Video',
        'kenyataan' => 'Kenyataan / Percakapan Bertulis',
        'lain' => 'Lain-lain',
    ];

    public const STATUS = [
        'belum_disahkan' => 'Belum Disahkan',
        'disahkan' => 'Disahkan',
        'ditolak' => 'Ditolak',
    ];

    public function aduan()
    {
        return $this->belongsTo(Aduan::class);
    }

    public function pemuatnaik()
    {
        return $this->belongsTo(User::class, 'dimuatnaik_oleh');
    }

    public function pengesah()
    {
        return $this->belongsTo(User::class, 'disahkan_oleh');
    }

    public function saizPaparan(): string
    {
        if (! $this->saiz) {
            return '-';
        }

        return $this->saiz >= 1048576
            ? number_format($this->saiz / 1048576, 1).' MB'
            : number_format($this->saiz / 1024, 0).' KB';
    }
}
