<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;

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
        Inertia::share(['error' => function() {
            return \Session::get('error') ?? null;
        }]);
        Inertia::share(['success' => function() {
            return \Session::get('success') ?? null;
        }]);
    }
}
