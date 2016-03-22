<?php
namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use AppBundle\Entity as Entity;

use ApiBundle\Repository\AbstractRepository;
use ApiBundle\Repository\RepositoryInterface;
use ApiBundle\Repository\ApiCriteria;
use ApiBundle\Repository\ApiQuery;

class CacheRepository extends AbstractRepository
{
    private $name = 'cache';

    public function saveItem($item, $immediate = true)
    {
        $this->getEntityManager()->persist($item);
        if ($immediate) {
            $this->getEntityManager()->flush();
        }
        return $item;
    }

    public function deleteItem($item, $immediate = true)
    {
        throw new \Exception("Not allowed to delete {$this->name} through this API.");
    }

    public function findItem(ApiCriteria $criteria, $hydration = 2)
    {
        $query = $this
            ->createQueryBuilder('root')
            ->select('PARTIAL root.{id, type, cache_key, value, last_update} as item')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $results = $apiQuery->queryItem($hydration);
        return $results;
    }

    public function findList(ApiCriteria $criteria, $hydration = 2)
    {
        throw new \Exception("Not allowed to list {$this->name} through this API.");
    }

    public function addTypeFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.type = :type');
        $queryBuilder->setParameter('type', $value);
    }

    public function addKeyFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.cache_key = :key');
        $queryBuilder->setParameter('key', $value);
    }

    public function transformArrayResult(ApiCriteria $criteria, &$item)
    {
        parent::transformArrayResult($criteria, $item);
    }
}
