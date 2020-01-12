<?php

namespace PulkitJalan\GeoIP;

use Illuminate\Support\ServiceProvider;
use PulkitJalan\GeoIP\Console\UpdateCommand;

class GeoIPServiceProvider extends ServiceProvider
{
    /**
     * Boot the service provider.
     */
    public function boot()
    {
        $this->app['geoip'] = function ($app) {
            return $app['PulkitJalan\GeoIP\GeoIP'];
        };

        if ($this->app->runningInConsole()) {
            $this->commands(['PulkitJalan\GeoIP\Console\UpdateCommand']);
        }

        if (function_exists('config_path')) {
            $this->publishes([
                __DIR__.'/../config/geoip.php' => config_path('geoip.php'),
            ], 'config');
        }
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        if (method_exists($this, 'mergeConfigFrom')) {
            $this->mergeConfigFrom(__DIR__.'/../config/geoip.php', 'geoip');
        } else {
            $this->app->config->package('pulkitjalan/geoip', realpath(__DIR__.'/config'), 'geoip');
        }

        $this->registerGeoIP();

        $this->registerUpdateCommand();
    }

    /**
     * Register the main geoip wrapper.
     */
    protected function registerGeoIP()
    {
        if ($this->isLaravel4()) {
            $this->app['geoip'] = $this->app->share(function ($app) {
                return new GeoIP($app->config->get('geoip::config'));
            });
        } else {
            $this->app->singleton('PulkitJalan\GeoIP\GeoIP', function ($app) {
                return new GeoIP($app['config']['geoip']);
            });
        }
    }

    /**
     * Register the geoip update console command.
     */
    protected function registerUpdateCommand()
    {
        if ($this->isLaravel4()) {
            $this->app['command.geoip.update'] = $this->app->share(function ($app) {
                return new UpdateCommand($app->config->get('geoip::config'));
            });

            $this->commands(['command.geoip.update']);
        } else {
            $this->app->singleton('PulkitJalan\GeoIP\Console\UpdateCommand', function ($app) {
                return new UpdateCommand($app['config']['geoip']);
            });
        }
    }

    /**
     * Laravel Version Check
     *
     * @return bool
     */
    protected function isLaravel4()
    {
        if (method_exists($this->app, 'version')) {
            return version_compare($this->app->version(), '5', '<');
        }

        return true;
    }

    /**
     * Get the services provided by the provider.
     *
     * @return string[]
     */
    public function provides()
    {
        return [
            'PulkitJalan\GeoIP\GeoIP',
            'PulkitJalan\GeoIP\Console\UpdateCommand',
            'geoip',
        ];
    }
}
