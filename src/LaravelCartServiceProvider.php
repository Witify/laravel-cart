<?php

namespace Witify\LaravelCart;

use Illuminate\Auth\Events\Login;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;

class LaravelCartServiceProvider extends ServiceProvider
{

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('cart', 'Witify\LaravelCart\Cart');

        $config = __DIR__ . '/../config/cart.php';
        $this->mergeConfigFrom($config, 'cart');

        $this->publishes([__DIR__ . '/../config/cart.php' => config_path('cart.php')], 'config');

        $this->app['events']->listen(Login::class, function () {
            $this->app->make('cart')->updateDatabaseCart();
        });

        if ( ! class_exists('CreateCartsTable')) {
            // Publish the migration
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/0000_00_00_000000_create_carts_table.php' => database_path('migrations/'.$timestamp.'_create_carts_table.php'),
            ], 'migrations');
        }
    }
}
