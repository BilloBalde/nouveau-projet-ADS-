<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;

use App\Models\Sale;
use App\Models\Expense;
use App\Models\Achat;
use App\Observers\StockObserver;

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
        View::composer('layouts.head', function ($view) {
            try {
                $company = Company::latest()->first();
                $companyName = $company?->name ?? config('app.name');
            } catch (\Throwable $e) {
                $companyName = config('app.name');
            }

            $view->with('companyName', $companyName);
        });


        Sale::observe(StockObserver::class);
        Expense::observe(StockObserver::class);
        Achat::observe(StockObserver::class);

        // Directive @permission('sales.view') ... @endpermission
        Blade::if('permission', function (string $slug) {
            return auth()->check() && auth()->user()->hasPermission($slug);
        });
    }
}
