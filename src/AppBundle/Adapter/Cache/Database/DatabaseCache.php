<?php

namespace AppBundle\Adapter\Cache\Database;

use AppBundle\Adapter\Cache as Cache;
use ApiBundle\Repository\ApiCriteria;

class DatabaseCache implements Cache\CacheInterface
{
    private $entityManager;
    private $repository;

    public function __construct($entityManager, $repository)
    {
        $this->entityManager = $entityManager;
        $this->repository = $this->entityManager->getRepository($repository);
    }

    public function remember($type, $key, $callback) {
        // if (Configure::read('enabled') == 0) {
        //     return $callback();
        // }
        $cacheEntity = $this->findInCache($type, $key);
        if ($cacheEntity != null) {
            return $cacheEntity->value;
        }
        $value = $callback();
        $this->saveToCache($type, $key, $value);
        return $value;
    }

    private function findInCache($type, $key)
    {
        $apiCriteria = new ApiCriteria([
            'type' => $type,
            'key' => $key,
        ]);
        $cacheEntity = $this->repository->findItem($apiCriteria, 1)['data'];
        if ($cacheEntity != null) {
            return $cacheEntity;
        }
        return null;
    }

    private function saveToCache($type, $key, $value)
    {
        $className = $this->repository->getClassName();
        $cacheEntity = new $className();
        $cacheEntity->type = $type;
        $cacheEntity->cache_key = $key;
        $cacheEntity->value = $value;
        $cacheEntity->last_update = new \DateTime();
        $this->repository->saveItem($cacheEntity);
    }
}