<?php

namespace App\Providers;

use App\Models\Company;
use App\Models\Expense;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use App\Observers\StockObserver;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Schema::defaultStringLength(191);

        // Le rendu par défaut des liens de pagination cible Tailwind ; les pages
        // admin sont en Bootstrap, où ses SVG s'affichent à taille brute (géants).
        Paginator::useBootstrapFive();
        // Ces vues interrogeaient toutes `Company::latest()->first()` en dur, sans
        // garde sur le null. Elles reçoivent désormais $company, résolu une seule
        // fois par requête. Les vues servies par un contrôleur qui passe déjà
        // `company` (devis, reçus, exports) ne sont pas listées ici.
        View::composer([
            'layouts.head',
            'layouts.header',
            'users.login',
            'factures.show',
            'factures.bon_de_commande',
            'factures.bon_de_sortie',
            'reports.daily-pdf',
        ], function ($view) {
            $view->with('company', Company::current());
        });

        Sale::observe(StockObserver::class);
        Expense::observe(StockObserver::class);
        PurchaseOrder::observe(StockObserver::class);

        // Directive @permission('sales.view') ... @endpermission
        Blade::if('permission', function (string $slug) {
            return auth()->check() && auth()->user()->hasPermission($slug);
        });
    }
}
