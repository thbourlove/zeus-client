<?php
namespace Eleme\Zeus\Tests;

use Mockery;
use Eleme\Zeus\Zeus;
use PHPUnit_Framework_TestCase;

class ZeusTest extends PHPUnit_Framework_TestCase
{
    public function testAuthorization()
    {
        $authorizations = array('auth');
        Zeus::setAuthorizations($authorizations);
        $this->assertEquals($authorizations, Zeus::$authorizations);
    }

    public function testTimer()
    {
        $timer = Mockery::mock('timer');
        Zeus::setTimer($timer);
        $this->assertEquals($timer, Zeus::getTimer());
    }

    public function testTimerClosure()
    {
        $timer = Mockery::mock('timer');
        $closure = function () use ($timer) {
            return $timer;
        };
        Zeus::setTimer($closure);
        $this->assertEquals($timer, Zeus::getTimer());
    }

    public function testCacher()
    {
        $cacher = Mockery::mock('cacher');
        Zeus::setCacher($cacher);
        $this->assertEquals($cacher, Zeus::getCacher());
    }

    public function testCacherClosure()
    {
        $cacher = Mockery::mock('cacher');
        $closure = function () use ($cacher) {
            return $cacher;
        };
        Zeus::setCacher($closure);
        $this->assertEquals($cacher, Zeus::getCacher());
    }

    public function testLogger()
    {
        $logger = Mockery::mock('logger');
        Zeus::setLogger($logger);
        $this->assertEquals($logger, Zeus::getLogger());
    }

    public function testLoggerClosure()
    {
        $logger = Mockery::mock('logger');
        $closure = function () use ($logger) {
            return $logger;
        };
        Zeus::setLogger($closure);
        $this->assertEquals($logger, Zeus::getLogger());
    }

    public function testClients()
    {
        $clients = Mockery::mock('clients');
        Zeus::setClients($clients);
        $this->assertEquals($clients, Zeus::getClients());
    }

    public function testClientsClosure()
    {
        $clients = Mockery::mock('clients');
        $closure = function () use ($clients) {
            return $clients;
        };
        Zeus::setClients($closure);
        $this->assertEquals($clients, Zeus::getClients());
    }

    public function testFactory()
    {
        $this->assertInstanceOf('Eleme\Zeus\Wrapper', Zeus::factory('foo'));
    }
}
