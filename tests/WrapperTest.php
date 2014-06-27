<?php
namespace Eleme\Zeus\Tests;

use PHPUnit_Framework_TestCase;
use Eleme\Zeus\Wrapper;

class WrapperTest extends PHPUnit_Framework_TestCase
{
    public $wrapper = null;
    public $client = null;
    public $server = '';

    public function setUp()
    {
        $this->client = $this->getMock('Client');
        $this->server = 'test';
        $clients = array($this->server => $this->client);
        $this->wrapper = new Wrapper($clients, $this->server);
    }

    public function testFluentInterface()
    {
        $result = $this->wrapper->call('api')->with('args')->run();
        $this->assertNull($result);
    }
}
