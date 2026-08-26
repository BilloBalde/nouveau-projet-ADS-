<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // Rappel hebdomadaire des dettes impayées — samedi 18h (Conakry).
        // Nécessite un cron `php artisan schedule:run` (prod) ou `php artisan schedule:work` (local).
        $schedule->command('debt:send-reminders')
            ->weeklyOn(6, '18:00')
            ->timezone('Africa/Conakry');

        // Bascule quotidienne des achats "en attente" dont la date de réception est
        // arrivée vers "reçu" — sans ça, un achat créé avec une date future reste
        // affiché "en attente" indéfiniment si personne ne rouvre la fiche pour la
        // modifier.
        $schedule->command('purchase-orders:refresh-status')
            ->dailyAt('00:10')
            ->timezone('Africa/Conakry');

        // Sauvegarde quotidienne de la base, 14 jours conservés.
        // withoutOverlapping : un dump encore en cours ne doit pas en croiser un
        // second, qui produirait deux fichiers concurrents et doublerait la charge.
        // Attention : la copie reste sur le même disque que la base. Elle protège
        // d'une fausse manœuvre, pas d'une panne matérielle — voir LIVRAISON.md.
        $schedule->command('db:backup')
            ->dailyAt('02:00')
            ->timezone('Africa/Conakry')
            ->withoutOverlapping();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
