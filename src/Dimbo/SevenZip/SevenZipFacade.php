<?php namespace Dimbo\SevenZip;

use Illuminate\Support\Facades\Facade;

class SevenZipFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'seven-zip';
    }
}
