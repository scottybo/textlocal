<?php
namespace App\TextLocal\Test;
use Orchestra\Testbench\TestCase as Orchestra;
use App\TextLocal\TextLocalFacade;

abstract class TestCase extends Orchestra
{
    /**
     * @param \Illuminate\Foundation\Application $app
     *
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            TextLocal::class,
        ];
    }
    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('laravel-twitter-streaming-api', [
            'key' => 'my_key',
            'username' => 'my_username',
            'hash' => 'my_hash',
            'url' => 'https://api.txtlocal.com/',
        ]);
    }
    protected function getPackageAliases($app)
    {
        return [
            'TextLocal' => TextLocalFacade::class,
        ];
    }
}