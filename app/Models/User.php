<?php

namespace App\Models;

use App\Enums\Peranan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'no_pekerja', 'jawatan', 'telefon', 'peranan', 'aktif',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'peranan' => Peranan::class,
            'aktif' => 'boolean',
            'log_masuk_terakhir' => 'datetime',
        ];
    }

    public function ada(Peranan ...$peranan): bool
    {
        return in_array($this->peranan, $peranan, true);
    }

    public function isPentadbir(): bool
    {
        return $this->peranan === Peranan::Pentadbir;
    }

    public function isKetuaUnit(): bool
    {
        return $this->peranan === Peranan::KetuaUnit;
    }

    public function isPegawaiSiasatan(): bool
    {
        return $this->peranan === Peranan::PegawaiSiasatan;
    }

    public function aduanDisiasat()
    {
        return $this->hasMany(Aduan::class, 'pegawai_siasatan_id');
    }

    public function scopeAktif($q)
    {
        return $q->where('aktif', true);
    }

    public function scopePeranan($q, Peranan ...$peranan)
    {
        return $q->whereIn('peranan', array_map(fn ($p) => $p->value, $peranan));
    }
}
