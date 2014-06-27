<?php
namespace Eleme\Zeus\Provider\Silex;

use Silex\Application;
use Silex\ServiceProviderInterface;
use Eleme\Zeus\Zeus;

class ZeusServiceProvider implements ServiceProviderInterface
{
    public function register(Application $app)
    {
        $app['zeus'] = $app->share(function () {
            return new Zeus();
        });
        $app['zeus.authorizations'] = array();
    }

    public function boot(Application $app)
    {
        Zeus::setClients(function () use ($app) {
            return $app['thrift.clients'];
        });
        Zeus::setTimer(function () use ($app) {
            return $app['timer.collections']['zeus'];
        });
        Zeus::setLogger(function () use ($app) {
            return $app['loggers']['zeus'];
        });
        Zeus::setCacher(function () use ($app) {
            return $app['cacher'];
        });
        Zeus::setAuthorizations($app['zeus.authorizations']);
    }
}
