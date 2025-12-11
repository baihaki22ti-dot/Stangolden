<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\User;
use DateTime;

class AccountApproved extends Notification implements ShouldQueue
{
    use Queueable;

    public $tries = 3;           // coba 3 kali
    public $timeout = 60;        // 60 detik timeout

    public function retryUntil(): DateTime
    {
        return now()->addMinutes(5)->toDateTime();
    }

    public function __construct(
        public User $user,
        public ?string $loginUrl = null,
        public ?string $expiresAt = null,
        public bool $upkp = false,
        public bool $tugasBelajar = false
    ) {}

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $appName = config('app.name');
        $url = $this->loginUrl ?? config('app.frontend_url') ?? url('/login');

        return (new MailMessage)
            ->subject("Akun Anda telah disetujui - {$appName}")
            ->markdown('emails.account_approved', [
                'user' => $this->user,
                'appName' => $appName,
                'url' => $url,
                'expiresAt' => $this->expiresAt,
                'upkp' => $this->upkp,
                'tugasBelajar' => $this->tugasBelajar,
            ]);
    }
}