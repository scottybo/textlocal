<?php
namespace App\TextLocal;
use Illuminate\Support\ServiceProvider;

class TextLocalServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {

    }
    /**
     * Register the application services.
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/textlocal.php', 'textlocal');
        $this->app->bind('textlocal', TextLocalClass::class);
    }
}