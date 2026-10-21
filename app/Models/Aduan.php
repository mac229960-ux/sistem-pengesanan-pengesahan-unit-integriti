<?php

namespace App\Models;

use App\Enums\KeputusanSiasatan;
use App\Enums\StatusAduan;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

class Aduan extends Model
{
    use SoftDeletes;

    protected $table = 'aduan';

    protected $fillable = [
        'no_rujukan', 'saluran_id', 'kategori_id', 'no_rujukan_saluran', 'tarikh_terima', 'tarikh_kejadian',
        'tanpa_nama', 'nama_pengadu', 'no_kp_pengadu', 'telefon_pengadu', 'emel_pengadu', 'alamat_pengadu',
        'tajuk', 'keterangan', 'lokasi', 'jabatan_terlibat_id', 'keutamaan',
        'status', 'pegawai_siasatan_id', 'diagih_oleh', 'tarikh_agihan', 'tarikh_akhir_sla', 'tarikh_selesai',
        'keputusan_siasatan', 'sebab_tidak_diteruskan', 'retensi_hingga', 'dicipta_oleh',
    ];

    protected $casts = [
        'tarikh_terima' => 'date',
        'tarikh_kejadian' => 'date',
        'tarikh_agihan' => 'datetime',
        'tarikh_akhir_sla' => 'date',
        'tarikh_selesai' => 'datetime',
        'retensi_hingga' => 'date',
        'tanpa_nama' => 'boolean',
        'status' => StatusAduan::class,
        'keputusan_siasatan' => KeputusanSiasatan::class,
        // Maklumat peribadi pengadu disulitkan dalam pangkalan data (PDPA).
        'nama_pengadu' => 'encrypted',
        'no_kp_pengadu' => 'encrypted',
        'telefon_pengadu' => 'encrypted',
        'emel_pengadu' => 'encrypted',
        'alamat_pengadu' => 'encrypted',
    ];

    public const KEUTAMAAN = [
        'rendah' => 'Rendah',
        'sederhana' => 'Sederhana',
        'tinggi' => 'Tinggi',
        'kritikal' => 'Kritikal',
    ];

    // ---------- Hubungan ----------
    public function saluran()
    {
        return $this->belongsTo(SaluranAduan::class, 'saluran_id');
    }

    public function kategori()
    {
        return $this->belongsTo(KategoriAduan::class, 'kategori_id');
    }

    public function jabatanTerlibat()
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_terlibat_id');
    }

    public function pegawaiSiasatan()
    {
        return $this->belongsTo(User::class, 'pegawai_siasatan_id');
    }

    public function pencipta()
    {
        return $this->belongsTo(User::class, 'dicipta_oleh');
    }

    public function pihakTerlibat()
    {
        return $this->hasMany(AduanPihakTerlibat::class);
    }

    public function aktiviti()
    {
        return $this->hasMany(SiasatanAktiviti::class)->orderByDesc('tarikh_aktiviti');
    }

    public function bukti()
    {
        return $this->hasMany(Bukti::class)->orderBy('id');
    }

    public function aiCadangan()
    {
        return $this->hasMany(AiCadangan::class)->latest();
    }

    public function cadanganTindakan()
    {
        return $this->hasMany(CadanganTindakan::class)->orderByDesc('versi');
    }

    public function kelulusan()
    {
        return $this->hasMany(Kelulusan::class)->latest();
    }

    public function tindakanDiambil()
    {
        return $this->hasMany(TindakanDiambil::class)->orderByDesc('tarikh_tindakan');
    }

    public function sejarahStatus()
    {
        return $this->hasMany(StatusSejarah::class)->orderByDesc('id');
    }

    public function cadanganTerkini()
    {
        return $this->hasOne(CadanganTindakan::class)->latestOfMany('versi');
    }

    // ---------- SLA ----------
    public function bakiHari(): ?int
    {
        if (! $this->status->aktif()) {
            return null;
        }

        return (int) Carbon::today()->diffInDays($this->tarikh_akhir_sla, false);
    }

    /** hijau | kuning | merah | lewat | selesai */
    public function tahapSla(): string
    {
        $baki = $this->bakiHari();
        if ($baki === null) {
            return 'selesai';
        }

        return match (true) {
            $baki < 0 => 'lewat',
            $baki <= 30 => 'merah',
            $baki <= 90 => 'kuning',
            default => 'hijau',
        };
    }

    public function selesaiDalamSla(): ?bool
    {
        if (! $this->tarikh_selesai) {
            return null;
        }

        return $this->tarikh_selesai->startOfDay()->lte($this->tarikh_akhir_sla);
    }

    public function namaPengaduPaparan(): string
    {
        return $this->tanpa_nama || ! $this->nama_pengadu ? 'Tanpa Nama' : $this->nama_pengadu;
    }

    // ---------- Skop ----------
    public function scopeAktif($q)
    {
        return $q->whereIn('status', StatusAduan::aktifValues());
    }

    public function scopeLewat($q)
    {
        return $q->aktif()->whereDate('tarikh_akhir_sla', '<', today());
    }

    public function scopeBolehDilihat($q, User $user)
    {
        // Pegawai Siasatan hanya melihat kes yang diagihkan kepadanya.
        if ($user->isPegawaiSiasatan()) {
            $q->where('pegawai_siasatan_id', $user->id);
        }

        return $q;
    }

    public static function janaNoRujukan(?Carbon $tarikh = null): string
    {
        $tahun = ($tarikh ?? now())->year;
        $prefix = "UI/{$tahun}/";
        $akhir = static::withTrashed()->where('no_rujukan', 'like', $prefix.'%')
            ->lockForUpdate()->orderByDesc('no_rujukan')->value('no_rujukan');
        $seterusnya = $akhir ? ((int) substr($akhir, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $seterusnya, 5, '0', STR_PAD_LEFT);
    }
}
