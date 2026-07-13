<?php

namespace App\Console\Commands;

use App\Models\DebtReminder;
use App\Models\Dette;
use App\Models\Facture;
use App\Models\User;
use App\Notifications\DebtReminderDigestNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendDebtReminders extends Command
{
    protected $signature = 'debt:send-reminders';

    protected $description = 'Recense les dettes impayées, met à jour les rappels et notifie le staff (digest hebdomadaire)';

    public function handle(): int
    {
        // 1. Marquer résolus les rappels dont la dette/facture est désormais soldée
        $resolved = 0;
        DebtReminder::where('status', '!=', 'resolved')
            ->with(['dette', 'facture'])
            ->get()
            ->each(function (DebtReminder $reminder) use (&$resolved) {
                $detteOk   = $reminder->dette_id && ($reminder->dette === null || $reminder->dette->reste <= 0 || $reminder->dette->status === 'paid');
                $factureOk = $reminder->facture_id && ($reminder->facture === null || $reminder->facture->reste <= 0 || $reminder->facture->statut === 'payé');
                if ($detteOk || $factureOk) {
                    $reminder->update(['status' => 'resolved']);
                    $resolved++;
                }
            });

        // 2. Dettes impayées (module dettes)
        $dettes = Dette::with('customer')
            ->where('status', 'pending')
            ->where('reste', '>', 0)
            ->get();

        // 3. Factures impayées ou partielles
        $factures = Facture::with('customer')
            ->whereIn('statut', ['non payé', 'partiel'])
            ->where('reste', '>', 0)
            ->get();

        $customers = [];

        $touch = function (array $keys, float $reste) use (&$customers) {
            $reminder = DebtReminder::firstOrNew($keys);
            $reminder->amount         = $reste;
            $reminder->reminder_count = $reminder->exists ? $reminder->reminder_count + 1 : 1;
            $reminder->last_sent_at   = now();
            $reminder->status         = 'sent';
            $reminder->save();

            $customers[$keys['customer_id']] = ($customers[$keys['customer_id']] ?? 0) + $reste;
        };

        foreach ($dettes as $dette) {
            if (! $dette->customer) continue;
            $touch(['customer_id' => $dette->customer_id, 'dette_id' => $dette->id], (float) $dette->reste);
        }

        foreach ($factures as $facture) {
            if (! $facture->customer) continue;
            $touch(['customer_id' => $facture->customer_id, 'facture_id' => $facture->id], (float) $facture->reste);
        }

        $customerCount = count($customers);
        $totalAmount   = array_sum($customers);

        // 4. Digest au staff (admins + managers)
        if ($customerCount > 0) {
            $staff = User::whereIn('role_id', [User::ROLE_ADMIN, User::ROLE_MANAGER])->get();
            Notification::send($staff, new DebtReminderDigestNotification($customerCount, $totalAmount));
        }

        $this->info("Rappels traités : {$customerCount} client(s), " . number_format($totalAmount, 0, ',', ' ') . " GNF impayés, {$resolved} rappel(s) résolu(s).");

        return self::SUCCESS;
    }
}
