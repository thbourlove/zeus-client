<?php
namespace Eleme\Zeus\Tests\Provider\Silex;

use PHPUnit_Framework_TestCase;
use Silex\Application;
use Eleme\Zeus\Provider\Silex\ZeusServiceProvider;
use Symfony\Component\HttpFoundation\Request;

class ZeusServiceProviderTest extends PHPUnit_Framework_TestCase
{
    public function testRegister()
    {
        $app = new Application();
        $app->register(new ZeusServiceProvider);
        $app['thrift.clients'] = null;
        $app['timer.collections'] = array('zeus' => null);
        $app['loggers'] = array('zeus' => null);
        $app['cacher'] = null;
        $app['zeus.authorizations'] = array();

        $app->get('/', function () {
        });
        $request = Request::create('/');
        $app->handle($request);

        $this->assertInstanceOf('Eleme\Zeus\Zeus', $app['zeus']);
        $this->assertInstanceOf('Eleme\Zeus\Wrapper', $app['zeus']->factory('foo'));
    }
}
