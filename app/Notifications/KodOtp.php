<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class KodOtp extends Notification
{
    public function __construct(private string $kod, private int $minit, private string $nama, private string $emelPengguna) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Kod OTP Log Masuk – Sistem Integriti MBSP')
            ->greeting('Salam sejahtera '.$this->nama.',')
            ->line("Akaun: {$this->emelPengguna}")
            ->line('Kod OTP anda untuk log masuk ke Sistem Pengesanan dan Pengesahan Unit Integriti ialah:')
            ->line('**'.$this->kod.'**')
            ->line("Kod ini sah selama {$this->minit} minit. Jangan kongsi kod ini dengan sesiapa.")
            ->line('Jika anda tidak cuba log masuk, sila maklumkan kepada Pentadbir Sistem dengan segera.')
            ->salutation('Unit Integriti, MBSP');
    }
}
