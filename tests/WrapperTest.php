<?php
namespace Eleme\Zeus\Tests;

use StdClass;
use RuntimeException;
use PHPUnit_Framework_TestCase;
use Eleme\Zeus\Wrapper;
use Mockery;
use TEST\TESTUserException;
use TEST\TESTSystemException;
use Eleme\Zeus\NullValue;

class WrapperTest extends PHPUnit_Framework_TestCase
{
    private $client = null;
    private $server = '';
    private $clients = array();
    private $timer = null;
    private $logger = null;
    private $cacher = null;

    public function setUp()
    {
        $this->client = Mockery::mock('client');
        $this->client
            ->shouldReceive('api')
            ->andReturn('result');
        $this->server = 'test';
        $this->clients = array($this->server => $this->client);
        $this->timer = Mockery::mock('timer');
        $this->logger = Mockery::mock('logger');
        $this->cacher = Mockery::mock('cacher');
    }

    /**
     * @expectedException BadMethodCallException
     */
    public function testNotCallableMethod()
    {
        $wrapper = $this->wrapper();
        $wrapper->call('not_exists')->with('args')->run();
    }

    public function testFluentInterface()
    {
        $wrapper = $this->wrapper();

        $result = $wrapper->call('api')->with('args')->run();
        $this->assertEquals('result', $result);
    }

    public function testTimer()
    {
        $that = $this;
        $server = $this->server;
        $this->timer
            ->shouldReceive('start')
            ->andReturnUsing(function ($name) use ($that, $server) {
                $that->assertEquals("{$server}.api", $name);
            });
        $this->timer
            ->shouldReceive('stop')
            ->andReturnUsing(function ($name) use ($that, $server) {
                $that->assertEquals("{$server}.api", $name);
            });
        $wrapper = $this->wrapper(array('timer' => $this->timer));
        $wrapper->call('api')->with('args')->run();
    }

    public function testLogWithoutPassword()
    {
        $that = $this;
        $server = $this->server;
        $this->logger
            ->shouldReceive('info')
            ->andReturnUsing(function ($message, $content) use ($that, $server) {
                $that->assertEquals("{$server}::api", $message);
                $that->assertEquals(array('username', 'password'), $content);
            })
            ->once();
        $wrapper = $this->wrapper(array('logger' => $this->logger));
        $wrapper->call('api')->with('username', 'password')->run();
    }

    public function testLogWithPassword()
    {
        $that = $this;
        $server = $this->server;
        $this->logger
            ->shouldReceive('info')
            ->andReturnUsing(function ($message, $content) use ($that, $server) {
                $that->assertEquals("{$server}::auth", $message);
                $that->assertEquals(array('username'), $content);
            })
            ->once();
        $this->client
            ->shouldReceive('auth')
            ->andReturn('success');
        $options = array('logger' => $this->logger, 'authorizations' => array('auth'));
        $wrapper = $this->wrapper($options);
        $wrapper->call('auth')->with('username', 'password')->run();
    }

    /**
     * @expectedException TEST\TESTUserException
     */
    public function testLogWithException()
    {
        require_once 'TESTUserException.php';
        $that = $this;
        $server = $this->server;
        $this->logger
            ->shouldReceive('error')
            ->andReturnUsing(function ($message, $content) use ($that, $server) {
                $that->assertEquals("{$server}::auth -> exception", $message);
                $that->assertEquals(array('username', 'password'), $content);
            })
            ->once();
        $this->client
            ->shouldReceive('auth')
            ->andThrow(new TESTUserException('exception'));
        $wrapper = $this->wrapper(array('logger' => $this->logger));
        $wrapper->call('auth')->with('username', 'password')->run();
    }

    public function testCacher()
    {
        $that = $this;
        $this->cacher
            ->shouldReceive('get')
            ->andReturn('cached result', new NullValue, null, null);

        $this->client
            ->shouldReceive('foo')
            ->andReturn('result', null, 'no cache');

        $this->cacher
            ->shouldReceive('set')
            ->andReturnUsing(function ($key, $result, $ttl) use ($that) {
                $that->assertEquals(1, $ttl);
                $that->assertEquals('result', $result);
            }, function ($key, $result, $ttl) use ($that) {
                $that->assertEquals(10, $ttl);
                $that->assertInstanceOf('Eleme\Zeus\NullValue', $result);
            });
        $options = array('cacher' => $this->cacher);
        $that = $this;
        $this->assertEquals('cached result', $this->wrapper($options)->call('foo')->cache(1)->run());
        $this->assertNull($this->wrapper($options)->call('foo')->cache(1)->run());
        $this->assertEquals('result', $this->wrapper($options)->call('foo')->cache(1)->run());
        $this->assertNull($this->wrapper($options)->call('foo')->cache(10)->run());
        $this->assertEquals('no cache', $this->wrapper($options)->call('foo')->run());
    }

    /**
     * @expectedException RuntimeException
     */
    public function testCachedException()
    {
        $this->cacher
            ->shouldReceive('get')
            ->andReturn(new RuntimeException);

        $options = array('cacher' => $this->cacher);
        $this->wrapper($options)->call('foo')->cache(1)->run();
    }

    public function testExecute()
    {
        $wrapper = $this->wrapper();
        $result = $wrapper->call('api')->execute();
        $this->assertTrue($result);
    }

    public function testHandleUserException()
    {
        require_once 'TESTUserException.php';

        $this->client
            ->shouldReceive('auth')
            ->andThrow(new TESTUserException);
        $wrapper = $this->wrapper();
        $result = $wrapper->call('auth')->execute(false);
        $this->assertFalse($result);
    }

    /**
     * @expectedException TEST\TESTSystemException
     */
    public function testHandleSystemException()
    {
        require_once 'TESTSystemException.php';

        $this->client
            ->shouldReceive('auth')
            ->andThrow(new TESTSystemException);
        $wrapper = $this->wrapper();
        $wrapper->call('auth')->execute(false);
    }

    public function testHandler()
    {
        require_once 'TESTSystemException.php';

        $this->client
            ->shouldReceive('auth')
            ->andThrow(new TESTSystemException);
        $wrapper = $this->wrapper();
        $result = $wrapper->call('auth')->handler(function ($e) {
            return 'handler';
        })->execute();
        $this->assertEquals('handler', $result);
    }

    public function testResult()
    {
        $this->client
            ->shouldReceive('get_foo')
            ->andReturn('foo')
            ->once();
        $this->assertEquals('foo', $this->wrapper()->call('get_foo')->result());
    }

    public function testResultWithEeception()
    {
        require_once 'TESTUserException.php';

        $this->client
            ->shouldReceive('get_foo')
            ->andThrow(new TESTUserException)
            ->once();
        $this->assertEquals('default', $this->wrapper()->call('get_foo')->result('default'));
    }

    public function testGet()
    {
        $options = array('class' => 'Eleme\Zeus\Tests\MockModel');
        $this->client
            ->shouldReceive('get_model')
            ->andReturn('model object')
            ->once();
        $this->assertInstanceOf('Eleme\Zeus\Tests\MockModel', $this->wrapper($options)->call('get_model')->get());
    }

    public function testQuery()
    {
        $options = array('class' => 'Eleme\Zeus\Tests\MockModel');
        $this->client
            ->shouldReceive('get_models')
            ->andReturn(array('foo', 'bar'))
            ->once();
        $result = $this->wrapper($options)->call('get_models')->query();
        $this->assertTrue(is_array($result));
        $this->assertInstanceOf('Eleme\Zeus\Tests\MockModel', reset($result));
    }

    public function testFormatResult()
    {
        $options = array('class' => 'Eleme\Zeus\Tests\MockModel');
        $foo = json_decode(json_encode(array('id' => 1, 'name' => 'foo', 'tag' => 'f')));
        $bar = json_decode(json_encode(array('id' => 2, 'name' => 'bar', 'tag' => 'b')));
        $far = json_decode(json_encode(array('id' => 3, 'name' => 'far', 'tag' => 'f')));
        $this->client
            ->shouldReceive('get_models')
            ->andReturn(array($foo, $bar, $far))
            ->times(4);

        $result = $this->wrapper($options)->call('get_models')->query(array(), 'name');
        $this->assertTrue(is_array($result));
        $this->assertInstanceOf('Eleme\Zeus\Tests\MockModel', $result['foo']);

        $result = $this->wrapper($options)->call('get_models')->query(array(), 'tag', false);
        $this->assertTrue(is_array($result['f']));
        $this->assertInstanceOf('Eleme\Zeus\Tests\MockModel', $result['f'][1]);

        $callback = function ($object) {
            return $object->id;
        };
        $result = $this->wrapper($options)->call('get_models')->query(array(), $callback, false);
        $this->assertTrue(is_array($result[1]));
        $this->assertInstanceOf('Eleme\Zeus\Tests\MockModel', $result[1][0]);

        $result = $this->wrapper($options)->call('get_models')->query(array(), $callback, true);
        $this->assertTrue(is_array($result));
        $this->assertInstanceOf('Eleme\Zeus\Tests\MockModel', $result[2]);
    }

    private function wrapper(array $options = array())
    {
        return new Wrapper($this->clients, $this->server, $options);
    }
}
