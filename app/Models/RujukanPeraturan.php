<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RujukanPeraturan extends Model
{
    protected $table = 'rujukan_peraturan';

    protected $fillable = [
        'kod', 'tajuk', 'jenis', 'penerbit', 'tahun', 'sumber_url', 'ringkasan',
        'nama_fail', 'laluan_fail', 'teks_rasmi', 'aktif', 'dikemaskini_oleh',
    ];

    protected $casts = ['aktif' => 'boolean', 'teks_rasmi' => 'boolean'];

    public const JENIS = [
        'peraturan' => 'Peraturan',
        'akta' => 'Akta',
        'pekeliling' => 'Pekeliling',
        'garis_panduan' => 'Garis Panduan',
        'sop' => 'SOP / Prosedur',
    ];

    public function seksyen()
    {
        return $this->hasMany(RujukanSeksyen::class, 'rujukan_id')->orderBy('susunan');
    }
}
