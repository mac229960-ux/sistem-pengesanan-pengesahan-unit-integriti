<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RujukanSeksyen extends Model
{
    protected $table = 'rujukan_seksyen';

    protected $fillable = ['rujukan_id', 'no_seksyen', 'tajuk', 'kandungan', 'susunan'];

    public function rujukan()
    {
        return $this->belongsTo(RujukanPeraturan::class, 'rujukan_id');
    }

    public function label(): string
    {
        // No. seksyen dipaparkan hanya jika bernombor (cth. "Seksyen 23"), bukan label topik.
        $no = $this->no_seksyen && preg_match('/\d/', $this->no_seksyen) ? $this->no_seksyen.' – ' : '';

        return trim(($this->rujukan?->tajuk ?? '').' — '.$no.($this->tajuk ?? ''));
    }
}
