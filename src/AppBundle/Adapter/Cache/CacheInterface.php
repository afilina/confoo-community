<?php
namespace AppBundle\Adapter\Cache;

interface CacheInterface
{
    function remember($type, $key, $callback);
}
