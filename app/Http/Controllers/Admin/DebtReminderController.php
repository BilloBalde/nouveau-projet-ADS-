<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\DebtReminder;
use App\Services\SmsService;

class DebtReminderController extends Controller
{
    public function __construct(private SmsService $sms)
    {
    }

    /**
     * Liste des rappels de dettes groupés par client, avec liens WhatsApp.
     */
    public function index()
    {
        $reminders = DebtReminder::with('customer')
            ->where('status', '!=', 'resolved')
            ->get()
            ->groupBy('customer_id')
            ->map(function ($group) {
                $customer = $group->first()->customer;

                return (object) [
                    'customer'      => $customer,
                    'total'         => $group->sum('amount'),
                    'count'         => $group->count(),
                    'max_reminders' => $group->max('reminder_count'),
                    'last_sent_at'  => $group->max('last_sent_at'),
                    'reminder_ids'  => $group->pluck('id'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        return view('admin.debt-reminders.index', compact('reminders'));
    }

    /**
     * Marquer tous les rappels d'un client comme résolus.
     */
    public function resolve(int $customerId)
    {
        DebtReminder::where('customer_id', $customerId)
            ->where('status', '!=', 'resolved')
            ->update(['status' => 'resolved']);

        return back()->with('success', 'Rappels marqués comme résolus.');
    }

    /**
     * Envoie manuellement un rappel par SMS (Nimba SMS) à un client — même
     * principe que le bouton WhatsApp existant, mais déclenché explicitement
     * par le staff (un SMS coûte un crédit Nimba, pas d'envoi automatique).
     */
    public function sendSms(int $customerId)
    {
        $customer = Customer::findOrFail($customerId);

        $tel = preg_replace('/\D/', '', $customer->tel ?? '');
        if (strlen($tel) < 8) {
            return back()->with('error', 'Numéro de téléphone invalide pour ce client.');
        }

        $reminders = DebtReminder::where('customer_id', $customerId)
            ->where('status', '!=', 'resolved')
            ->get();
        $total = $reminders->sum('amount');

        if ($total <= 0) {
            return back()->with('error', 'Aucune dette active pour ce client.');
        }

        $message = 'Bonjour ' . ($customer->customerName ?? 'cher client')
            . ', vous avez une facture impayée de '
            . number_format($total, 0, ',', ' ')
            . ' GNF chez SMH. Veuillez régler votre dette avant lundi. Merci de votre confiance.';

        $sent = $this->sms->send($tel, $message);

        if ($sent) {
            foreach ($reminders as $reminder) {
                $reminder->update([
                    'reminder_count' => $reminder->reminder_count + 1,
                    'last_sent_at'   => now(),
                    'status'         => 'sent',
                ]);
            }

            return back()->with('success', "SMS envoyé à {$customer->customerName}.");
        }

        return back()->with('error', "Échec de l'envoi du SMS à {$customer->customerName} — vérifiez les logs.");
    }
}
