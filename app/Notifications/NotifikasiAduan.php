<?php

namespace App\Notifications;

use App\Models\Aduan;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifikasi aliran kerja aduan (BRS-022): dihantar melalui e-mel dan dalam sistem.
 * Kandungan e-mel sengaja ringkas — butiran kes hanya boleh dilihat selepas log masuk.
 */
class NotifikasiAduan extends Notification
{
    public function __construct(
        public Aduan $aduan,
        public string $jenis,
        public string $tajuk,
        public string $mesej,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->aduan->no_rujukan}] {$this->tajuk}")
            ->greeting('Salam sejahtera '.$notifiable->name.',')
            ->line($this->mesej)
            ->line('No. Rujukan: '.$this->aduan->no_rujukan)
            ->action('Buka Kes', route('aduan.show', $this->aduan))
            ->line('E-mel ini dijana secara automatik. Maklumat kes adalah SULIT.')
            ->salutation(config('app.name').', MBSP');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'aduan_id' => $this->aduan->id,
            'no_rujukan' => $this->aduan->no_rujukan,
            'jenis' => $this->jenis,
            'tajuk' => $this->tajuk,
            'mesej' => $this->mesej,
        ];
    }
}
