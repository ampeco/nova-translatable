<?php

namespace YesWeDev\Nova\Translatable;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\ServiceProvider;

class FieldServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            dirname(__DIR__) . '/config/config.publish.php' => config_path('nova-translatable.php'),
        ]);

        Nova::serving(function (ServingNova $event) {
            Nova::script('translatable-2024-06-10', __DIR__ . '/../dist/js/field.js');
            Nova::style('translatable', __DIR__ . '/../dist/css/field.css');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            dirname(__DIR__) . '/config/config.default.php', 'nova-translatable'
        );
    }
}
