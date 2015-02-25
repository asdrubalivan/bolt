<?php

namespace Bolt\Provider;

use Bolt\Update\Check;
use Bolt\Update\Updater;
use Silex\Application;
use Silex\ServiceProviderInterface;

class UpdateServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['update'] = $app->share(
            function ($app) {
                $updater = new Updater($app);
                return $updater;
            }
        );

        $app['update.check'] = $app->share(
            function ($app) {
                $updater = new Check($app['guzzle.client']);
                return $updater;
            }
        );
    }

    public function boot(Application $app)
    {
    }
}
