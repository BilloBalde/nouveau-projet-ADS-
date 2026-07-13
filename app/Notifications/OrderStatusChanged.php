<?php
// app/Notifications/OrderStatusChanged.php
namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class OrderStatusChanged extends Notification implements ShouldQueue
{
    use Queueable;

    protected $order;

    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $statusMessage = $this->order->status === 'approved' 
            ? 'a été approuvée' 
            : 'a été refusée';

        return (new MailMessage)
                    ->subject('Mise à jour du statut de votre commande #' . $this->order->id)
                    ->line('Bonjour ' . $notifiable->name . ',')
                    ->line('Le statut de votre commande #' . $this->order->id . ' ' . $statusMessage . '.')
                    ->action('Voir ma commande', url('/orders/' . $this->order->id))
                    ->line('Merci de votre confiance !');
    }
}