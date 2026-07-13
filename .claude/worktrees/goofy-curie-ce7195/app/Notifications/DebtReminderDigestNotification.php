<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Digest hebdomadaire envoyé au staff : liste des clients ayant des dettes impayées.
 */
class DebtReminderDigestNotification extends Notification
{
    use Queueable;

    public function __construct(
        protected int $customerCount,
        protected float $totalAmount,
    ) {}

    public function via($notifiable)
    {
        return ['database'];
    }

    public function toArray($notifiable)
    {
        return [
            'customer_count' => $this->customerCount,
            'total_amount'   => $this->totalAmount,
            'message'        => $this->customerCount . ' client(s) ont des dettes impayées pour un total de '
                . number_format($this->totalAmount, 0, ',', ' ')
                . ' GNF — cliquez pour voir la liste et envoyer les rappels WhatsApp.',
            'url'            => route('admin.debt-reminders.index', absolute: false),
        ];
    }
}
