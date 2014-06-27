<?php
namespace Eleme\Zeus;

use Closure;
use Exception;
use RuntimeException;
use Thrift\Exception\TException;

class Wrapper
{
    private $clients = null;
    private $service = '';

    private $cacher = null;
    private $timer = null;
    private $logger = null;
    private $authorizations = array();
    private $class = '';

    private $method = '';
    private $handler = null;
    private $args = array();
    private $ttl = 0;

    public function __construct($clients, $service, array $options = array())
    {
        $this->clients = $clients;
        $this->service = $service;
        $this->init($options);
    }

    public function get($default = null)
    {
        $handler = $this->handler ?: $this->defaultHandler($default);
        try {
            return new $this->class($this->run());
        } catch (TException $e) {
            return $handler($e);
        }
    }

    public function query($default = array(), $key = '', $unique = true)
    {
        $handler = $this->handler ?: $this->defaultHandler($default);
        try {
            $tResults = $this->run();
        } catch (TException $e) {
            return $handler($e);
        }

        $results = array();
        if ($key) {
            $results = $this->formatResult($tResults, $key, $unique);
        } else {
            foreach ($tResults as $tResult) {
                $results[] = new $this->class($tResult);
            }
        }
        return $results;
    }

    private function formatResult($tResults, $key, $unique)
    {
        if ($unique) {
            if ($key instanceof Closure) {
                foreach ($tResults as $tResult) {
                    $results[$key($tResult)] = new $this->class($tResult);
                }
            } else {
                foreach ($tResults as $tResult) {
                    $results[$tResult->$key] = new $this->class($tResult);
                }
            }
        } else {
            if ($key instanceof Closure) {
                foreach ($tResults as $tResult) {
                    $results[$key($tResult)][] = new $this->class($tResult);
                }
            } else {
                foreach ($tResults as $tResult) {
                    $results[$tResult->$key][] = new $this->class($tResult);
                }
            }
        }
        return $results;
    }

    public function result($default = null)
    {
        $handler = $this->handler ?: $this->defaultHandler($default);
        try {
            return $this->run();
        } catch (TException $e) {
            return $handler($e);
        }
    }

    public function execute($default = false)
    {
        $handler = $this->handler ?: $this->defaultHandler($default);
        try {
            $this->run();
        } catch (TException $e) {
            return $handler($e);
        }
        return true;
    }

    public function run()
    {
        $ttl = $this->getTtl();
        $cacher = $this->cacher;

        if ($ttl && $cacher) {
            $key = $this->getCacheKey();
            $result = $cacher->get($key);
            if ($result !== null) {
                return $this->unpack($result);
            }
        }

        $result = $this->pack($this->doRun());

        if ($ttl && $cacher) {
            $cacher->set($key, $result, $ttl);
        }

        return $this->unpack($result);
    }

    public function call($method)
    {
        $this->method = $method;
        return $this;
    }

    public function with()
    {
        $this->args = func_get_args();
        return $this;
    }

    public function cache($ttl)
    {
        $this->ttl = $ttl;
        return $this;
    }

    public function handler(\Closure $handler)
    {
        $this->handler = $handler;
        return $this;
    }

    private function getTtl()
    {
        return $this->ttl;
    }

    private function doRun()
    {
        $client = $this->clients[$this->service];

        if ($timer = $this->timer) {
            $name = "{$this->service}.{$this->method}";
            $timer->start($name);
        }

        $result = call_user_func_array(array($client, $this->method), $this->args);

        if ($timer) {
            $timer->stop($name);
        }

        $this->log();

        return $result;
    }

    private function unpack($result)
    {
        if ($result instanceof NullValue) {
            return null;
        }
        if ($result instanceof Exception) {
            throw $result;
        }
        return $result;
    }

    private function pack($result)
    {
        if ($result === null) {
            return new NullValue();
        }
        return $result;
    }

    private function defaultHandler($default)
    {
        return function (TException $e) use ($default) {
            if (get_class($e) === strtoupper($this->service).'\\'.strtoupper($this->service).'UserException') {
                return $default;
            } else {
                throw $e;
            }
        };
    }

    private function getCacheKey()
    {
        return md5(sprintf('web.%s.%s.%s', $this->service, $this->method, json_encode($this->args)));
    }

    private function log()
    {
        if (!$this->logger) {
            return;
        }
        if (in_array($this->method, $this->authorizations) !== false) {
            $args = array_slice($this->args, 0, count($this->args) - 1);
        } else {
            $args = $this->args;
        }
        $this->logger->info($this->service.'::'.$this->method, $args);
    }

    private function init(array $options)
    {
        foreach ($options as $key => $option) {
            $this->$key = $option;
        }
    }
}
