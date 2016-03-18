<?php
namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use AppBundle\Entity as Entity;

use ApiBundle\Repository\AbstractRepository;
use ApiBundle\Repository\RepositoryInterface;
use ApiBundle\Repository\ApiCriteria;
use ApiBundle\Repository\ApiQuery;

class CfpAlertRepository extends AbstractRepository
{
    private $name = 'cfp alert';

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
        throw new \Exception("Not allowed to get item {$this->name} through this API.");
    }

    public function findList(ApiCriteria $criteria, $hydration = 2)
    {
        $query = $this
            ->createQueryBuilder('root')
            ->select('PARTIAL root.{id, tag, email, token, frequency, is_enabled, last_alert_date} as item')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $results = $apiQuery->queryList($hydration);
        return $results;
    }

    public function addEnabledFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.is_enabled = :is_enabled');
        $queryBuilder->setParameter('is_enabled', $value);
    }

    public function transformArrayResult(ApiCriteria $criteria, &$item)
    {
        parent::transformArrayResult($criteria, $item);
    }
}
