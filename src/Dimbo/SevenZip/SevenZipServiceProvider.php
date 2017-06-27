<?php namespace Dimbo\SevenZip;

use Illuminate\Support\ServiceProvider;

class SevenZipServiceProvider extends ServiceProvider
{

    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    public function boot()
    {
        $this->package('dimbo/seven-zip', 'seven-zip');
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind('seven-zip', function()
        {
            return new SevenZip($this->app['Psr\Log\LoggerInterface']);
        });
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return ['seven-zip'];
    }

}
