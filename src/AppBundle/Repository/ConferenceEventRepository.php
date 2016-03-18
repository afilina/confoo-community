<?php
namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use AppBundle\Entity as Entity;

use ApiBundle\Repository\AbstractRepository;
use ApiBundle\Repository\RepositoryInterface;
use ApiBundle\Repository\ApiCriteria;
use ApiBundle\Repository\ApiQuery;

class ConferenceEventRepository extends AbstractRepository
{
    private $name = 'conference event';

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
            ->select('PARTIAL root.{id, name, cfp_start, cfp_end, event_start, event_end} AS item')
            ->addSelect('PARTIAL conf.{id, name, website, twitter, tags}')
            ->innerJoin('root.conference', 'conf')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $result = $apiQuery->queryItem($hydration);
        return $result;
    }

    public function findList(ApiCriteria $criteria, $hydration = 2)
    {
        $criteria->allowIgnorePagination = true;

        $query = $this
            ->createQueryBuilder('root')
            ->select('PARTIAL root.{id, name, location, cfp_start, cfp_end, event_start, event_end} AS item')
            ->addSelect('PARTIAL conf.{id, name, website, twitter, tags}')
            ->innerJoin('root.conference', 'conf')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $results = $apiQuery->queryList($hydration);
        return $results;
    }

    public function addFilterJoins(QueryBuilder &$queryBuilder)
    {
        $queryBuilder->innerJoin('root.conference', 'conf');
    }

    public function addIdFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.id = :id');
        $queryBuilder->setParameter('id', $value);
    }

    public function addCfpStartFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.cfp_start <= :cfp_start');
        $queryBuilder->setParameter('cfp_start', $value);
    }

    public function addCfpEndFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.cfp_end >= :cfp_end');
        $queryBuilder->setParameter('cfp_end', $value);
    }

    public function addEventStartMinFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.event_start >= :event_start_min');
        $queryBuilder->setParameter('event_start_min', $value);
    }

    public function addEventStartMaxFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.event_start <= :event_start_max');
        $queryBuilder->setParameter('event_start_max', $value);
    }

    public function addTagFilter(QueryBuilder &$queryBuilder, $value)
    {
        if ($value == 'all') {
            return;
        }
        $queryBuilder->andWhere('conf.tags LIKE :tag');
        $queryBuilder->setParameter('tag', '%"'.$value.'"%');
    }

    public function addStartDateSort(QueryBuilder &$queryBuilder, $order)
    {
        $queryBuilder->addOrderBy('root.event_start', $order == '-' ? 'ASC' : 'DESC');
    }

    public function transformArrayResult(ApiCriteria $criteria, &$item)
    {
        parent::transformArrayResult($criteria, $item);
    }
}
