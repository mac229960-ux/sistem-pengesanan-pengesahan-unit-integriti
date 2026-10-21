<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiasatanAktiviti extends Model
{
    protected $table = 'siasatan_aktiviti';

    protected $fillable = ['aduan_id', 'user_id', 'jenis', 'tarikh_aktiviti', 'tajuk', 'butiran'];

    protected $casts = ['tarikh_aktiviti' => 'datetime'];

    public const JENIS = [
        'temubual' => 'Temu Bual / Rakaman Percakapan',
        'lawatan' => 'Lawatan / Pemeriksaan Lokasi',
        'semakan_dokumen' => 'Semakan Dokumen / Rekod',
        'surat' => 'Surat / Memo Dikeluarkan',
        'catatan' => 'Catatan Siasatan',
        'lain' => 'Lain-lain',
    ];

    public function aduan()
    {
        return $this->belongsTo(Aduan::class);
    }

    public function pegawai()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
