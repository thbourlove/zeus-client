<?php
namespace Eleme\Zeus\Tests;

class WrapperTest extends \PHPUnit_Framework_TestCase
{
    public $wrapper = null;
    public $clients = null;
    public $server = '';

    public function setUp()
    {
        $client = $this->getMock('Client');
        $this->server = 'test';
        $this->clients = array($this->server, $client);
        $this->wrapper = new Wrapper($this->clients, $this->server);
    }

    public function testFluentInterface()
    {
    }
}
