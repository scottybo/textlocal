<?php
namespace App\TextLocalApi;
use Illuminate\Support\ServiceProvider;

class TextLocalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/textlocal.php' => config_path('textlocal.php'),
            ], 'config');
        }
    }
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/textlocal.php', 'textlocal');
        $this->app->bind('textlocal', TextLocal::class);
    }
}