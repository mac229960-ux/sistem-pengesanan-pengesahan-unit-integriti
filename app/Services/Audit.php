<?php

namespace App\Services;

use App\Models\Aduan;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

/**
 * Jejak audit (BRS-009, BRS-023). Rekod hanya ditambah, tidak pernah dikemas kini.
 */
class Audit
{
    /** Medan yang tidak boleh direkod dalam log (maklumat sensitif). */
    private const DISEMBUNYI = [
        'password', 'remember_token', 'kod_hash',
        'nama_pengadu', 'no_kp_pengadu', 'telefon_pengadu', 'emel_pengadu', 'alamat_pengadu',
    ];

    public static function log(
        string $tindakan,
        ?Model $model = null,
        ?string $keterangan = null,
        ?array $lama = null,
        ?array $baru = null,
        ?int $aduanId = null,
        ?int $userId = null,
    ): AuditLog {
        $req = request();

        if ($aduanId === null && $model) {
            $aduanId = $model instanceof Aduan ? $model->id : ($model->aduan_id ?? null);
        }

        return AuditLog::create([
            'user_id' => $userId ?? auth()->id(),
            'tindakan' => $tindakan,
            'model_type' => $model ? class_basename($model) : null,
            'model_id' => $model?->getKey(),
            'aduan_id' => $aduanId,
            'keterangan' => $keterangan,
            'nilai_lama' => $lama ? self::bersih($lama) : null,
            'nilai_baru' => $baru ? self::bersih($baru) : null,
            'ip' => $req?->ip(),
            'user_agent' => substr((string) $req?->userAgent(), 0, 255),
            'url' => substr((string) $req?->fullUrl(), 0, 255),
        ]);
    }

    /** Log perubahan model selepas save() (guna getChanges()). */
    public static function perubahan(string $tindakan, Model $model, ?string $keterangan = null): ?AuditLog
    {
        $baru = Arr::except($model->getChanges(), ['updated_at']);
        if (! $baru) {
            return null;
        }
        $lama = array_intersect_key($model->getOriginal(), $baru);

        return self::log($tindakan, $model, $keterangan, $lama, $baru);
    }

    private static function bersih(array $data): array
    {
        foreach ($data as $k => $v) {
            if (in_array($k, self::DISEMBUNYI, true)) {
                $data[$k] = '[disembunyikan]';
            } elseif ($v instanceof \BackedEnum) {
                $data[$k] = $v->value;
            } elseif ($v instanceof \DateTimeInterface) {
                $data[$k] = $v->format('Y-m-d H:i:s');
            }
        }

        return $data;
    }
}
