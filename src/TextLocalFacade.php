<?php
namespace App\TextLocalApi;
use Illuminate\Support\Facades\Facade;
/**
 * @see App\TextLocal\TextLocalClass
 */
class TextLocalFacade extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'TextLocal';
    }
}