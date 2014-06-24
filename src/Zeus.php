<?php
namespace Eleme\Zeus;

use Closure;

class Zeus
{
    public static $clients = null;
    public static $cacher = null;
    public static $timer = null;
    public static $logger = null;
    public static $authorizations = array();

    public static function factory($service, $class = null)
    {
        $options = array(
            'cacher' => static::getCacher(),
            'timer' => static::getTimer(),
            'logger' => static::getLogger(),
            'authorizations' => static::$authorizations,
            'class' => $class ?: get_called_class(),
        );
        return new Wrapper(static::getClients(), $service, $options);
    }

    public static function setAuthorizations(array $authorizations)
    {
        static::$authorizations = $authorizations;
    }

    public static function setLogger($logger)
    {
        static::$logger = $logger;
    }

    public static function getLogger()
    {
        if (static::$logger instanceof Closure) {
            static::$logger = call_user_func(static::$logger);
        }
        return static::$logger;
    }

    public static function setTimer($timer)
    {
        static::$timer = $timer;
    }

    public static function getTimer()
    {
        if (static::$timer instanceof Closure) {
            static::$timer = call_user_func(static::$timer);
        }
        return static::$timer;
    }

    public static function setCacher($cacher)
    {
        static::$cacher = $cacher;
    }

    public static function getCacher()
    {
        if (static::$cacher instanceof Closure) {
            static::$cacher = call_user_func(static::$cacher);
        }
        return static::$cacher;
    }

    public static function setClients($clients)
    {
        static::$clients = $clients;
    }

    public static function getClients()
    {
        if (static::$clients instanceof Closure) {
            static::$clients = call_user_func(static::$clients);
        }
        return static::$clients;
    }
}
