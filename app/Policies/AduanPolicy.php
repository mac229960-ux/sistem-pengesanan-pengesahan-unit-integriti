<?php

namespace App\Policies;

use App\Enums\Peranan;
use App\Enums\StatusAduan;
use App\Models\Aduan;
use App\Models\User;

/**
 * Kawalan akses kes aduan (BRS-009, BRS-013).
 * - Pentadbir Sistem & Pengurusan: tiada akses butiran kes (hanya data agregat).
 * - Pembantu Tadbir: daftar & lihat maklumat asas aduan.
 * - Pegawai Siasatan: hanya kes yang diagihkan kepadanya.
 * - Ketua Unit Integriti: semua kes, agihan dan kelulusan.
 */
class AduanPolicy
{
    public function viewAny(User $u): bool
    {
        return $u->ada(Peranan::PembantuTadbir, Peranan::PegawaiSiasatan, Peranan::KetuaUnit);
    }

    public function view(User $u, Aduan $a): bool
    {
        return $u->ada(Peranan::PembantuTadbir, Peranan::KetuaUnit)
            || ($u->isPegawaiSiasatan() && $a->pegawai_siasatan_id === $u->id);
    }

    /** Akses butiran siasatan (aktiviti, bukti, AI, cadangan). */
    public function lihatSiasatan(User $u, Aduan $a): bool
    {
        return $u->isKetuaUnit() || ($u->isPegawaiSiasatan() && $a->pegawai_siasatan_id === $u->id);
    }

    public function create(User $u): bool
    {
        return $u->ada(Peranan::PembantuTadbir, Peranan::KetuaUnit);
    }

    public function update(User $u, Aduan $a): bool
    {
        return $u->ada(Peranan::PembantuTadbir, Peranan::KetuaUnit)
            && in_array($a->status, [StatusAduan::Baru, StatusAduan::Diagihkan], true);
    }

    public function agih(User $u, Aduan $a): bool
    {
        return $u->isKetuaUnit() && in_array($a->status, [
            StatusAduan::Baru, StatusAduan::Diagihkan, StatusAduan::DalamSiasatan, StatusAduan::Dikembalikan,
        ], true);
    }

    public function tidakDiteruskan(User $u, Aduan $a): bool
    {
        return $u->isKetuaUnit() && in_array($a->status, [StatusAduan::Baru, StatusAduan::Diagihkan], true);
    }

    /** Kemas kini siasatan: aktiviti, bukti, pihak terlibat, AI, cadangan. */
    public function siasat(User $u, Aduan $a): bool
    {
        $statusBoleh = in_array($a->status, [
            StatusAduan::Diagihkan, StatusAduan::DalamSiasatan, StatusAduan::Dikembalikan,
        ], true);

        return $statusBoleh && ($u->isKetuaUnit() || ($u->isPegawaiSiasatan() && $a->pegawai_siasatan_id === $u->id));
    }

    public function lulus(User $u, Aduan $a): bool
    {
        return $u->isKetuaUnit() && $a->status === StatusAduan::MenungguKelulusan;
    }

    public function rekodTindakan(User $u, Aduan $a): bool
    {
        return $a->status === StatusAduan::Diluluskan
            && ($u->isKetuaUnit() || ($u->isPegawaiSiasatan() && $a->pegawai_siasatan_id === $u->id));
    }

    public function tutup(User $u, Aduan $a): bool
    {
        return $u->isKetuaUnit() && $a->status === StatusAduan::Diluluskan;
    }
}
