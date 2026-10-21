<?php

namespace App\Services;

use App\Enums\Peranan;
use App\Models\Aduan;
use App\Models\User;
use App\Notifications\NotifikasiAduan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class Notifikasi
{
    public static function ketuaUnit(): Collection
    {
        return User::aktif()->peranan(Peranan::KetuaUnit)->get();
    }

    /** @param  iterable<User>|User|null  $penerima */
    public static function hantar($penerima, Aduan $aduan, string $jenis, string $tajuk, string $mesej): void
    {
        $senarai = collect($penerima instanceof User ? [$penerima] : ($penerima ?? []))
            ->filter()->unique('id')->filter(fn (User $u) => $u->aktif);

        if ($senarai->isEmpty()) {
            return;
        }

        try {
            Notification::send($senarai, new NotifikasiAduan($aduan, $jenis, $tajuk, $mesej));
        } catch (\Throwable $e) {
            // Kegagalan e-mel tidak boleh menggagalkan transaksi kes.
            report($e);
        }
    }
}
