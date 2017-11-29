<?php

namespace GlueNamespace\Framework\Foundation;

use GlueNamespace\Framework\Foundation\Provider;

class AppProvider extends Provider
{
    /**
     * The provider booting method to boot this provider
     * @param  GlueNamespace\Framework\Foundation\Application $app
     * @return void
     */
    public function booting()
    {
        $this->app->bindInstance(
            'app',
            $this->app,
            'App',
            'GlueNamespace\Framework\Foundation\Application'
        );
    }

    /**
     * The provider booted method to be called after booting
     * @param  GlueNamespace\Framework\Foundation\Application $app
     * @return void
     */
    public function booted()
    {   
        // Framework is booted and ready
        $this->app->booted(function($app) {
            $app->load($app->appPath('Global/Common.php'));
            $app->bootstrapWith($app->getCommonProviders());
        });

        // Application is booted and ready
        $this->app->ready(function($app) {
            $app->load($app->appPath('Hooks/Common.php'));
            if ($app->isUserOnAdminArea()) {
                $app->load($app->appPath('Hooks/Backend.php'));
            } else {
                $app->load($app->appPath('Hooks/Frontend.php'));   
            }
            $app->load($app->appPath('Hooks/Ajax.php'));
        });
    }
}
