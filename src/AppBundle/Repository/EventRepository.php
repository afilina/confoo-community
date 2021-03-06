<?php
namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use AppBundle\Entity as Entity;

use ApiBundle\Repository\AbstractRepository;
use ApiBundle\Repository\RepositoryInterface;
use ApiBundle\Repository\ApiCriteria;
use ApiBundle\Repository\ApiQuery;

class EventRepository extends AbstractRepository
{
    private $name = 'event';

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
            ->select('PARTIAL root.{id, name, location, cfp_start, cfp_end, event_start, event_end, cfp_website} AS item')
            ->addSelect('PARTIAL org.{id, name, locations, website, twitter, tags, type}')
            ->innerJoin('root.organization', 'org')
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
            ->select('PARTIAL root.{id, name, location, cfp_start, cfp_end, event_start, event_end, cfp_website} AS item')
            ->addSelect('PARTIAL org.{id, name, locations, website, twitter, tags, type}')
            ->innerJoin('root.organization', 'org')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $results = $apiQuery->queryList($hydration);
        return $results;
    }

    public function addFilterJoins(QueryBuilder &$queryBuilder)
    {
        $queryBuilder->innerJoin('root.organization', 'org');
    }

    public function addIdFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.id = :id');
        $queryBuilder->setParameter('id', $value);
    }

    public function addOrganizationFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.organization = :org_id');
        $queryBuilder->setParameter('org_id', $value);
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

    public function addCfpStartMinFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.cfp_start >= :cfp_start_min');
        $queryBuilder->setParameter('cfp_start_min', $value);
    }

    public function addCfpStartMaxFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.cfp_start <= :cfp_start_max');
        $queryBuilder->setParameter('cfp_start_max', $value);
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

    public function addEventEndMinFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.event_end >= :event_end_min');
        $queryBuilder->setParameter('event_end_min', $value);
    }

    public function addEventEndMaxFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.event_end <= :event_end_max');
        $queryBuilder->setParameter('event_end_max', $value);
    }

    public function addHasCoordsFilter(QueryBuilder &$queryBuilder, $value)
    {
        if ($value === false) {
            $queryBuilder->andWhere('root.latitude IS NULL');
            return;
        }
        if ($value === true) {
            $queryBuilder->andWhere('root.latitude IS NOT NULL');
            return;
        }
    }

    public function addNearLocationFilter(QueryBuilder &$queryBuilder, $value)
    {
        if ($value['unit'] == 'km') {
            $distanceMultiplier = 6371;
        }
        elseif ($value['unit'] == 'mile') {
            $distanceMultiplier = 3959;
        }
        else {
            throw new \Exception('Invalid distance unit');
        }

        $queryBuilder->addSelect('(
            '.$distanceMultiplier.' * acos (
              cos ( radians(:latitude) )
              * cos ( radians(root.latitude) )
              * cos( radians(root.longitude) - radians(:longitude) )
              + sin ( radians(:latitude) )
              * sin ( radians(root.latitude) )
            )
          ) distance');

        $queryBuilder->having('distance <= :radius');
        $queryBuilder->setParameter('latitude', $value['latitude']);
        $queryBuilder->setParameter('longitude', $value['longitude']);
        $queryBuilder->setParameter('radius', $value['radius']);
    }

    public function addCfpStatusFilter(QueryBuilder &$queryBuilder, $value)
    {
        $now = new \DateTime();
        if ($value == 'closed') {
            $queryBuilder->andWhere($queryBuilder->expr()->orX(
                $queryBuilder->expr()->isNull('root.cfp_end'),
                $queryBuilder->expr()->lt('root.cfp_end', ':now')
            ));
            $queryBuilder->setParameter('now', $now);
            return;
        }
        if ($value == 'open') {
            $queryBuilder->andWhere('root.cfp_start <= :now');
            $queryBuilder->andWhere('root.cfp_end >= :now');
            $queryBuilder->setParameter('now', $now);
            return;
        }
        if ($value == 'upcoming') {
            $queryBuilder->andWhere('root.cfp_start > :now');
            $queryBuilder->setParameter('now', $now);
            return;
        }
    }

    public function addTypeFilter(QueryBuilder &$queryBuilder, $value)
    {
        if ($value == 'all') {
            return;
        }
        $queryBuilder->andWhere('org.type = :type');
        $queryBuilder->setParameter('type', $value);
    }

    public function addTagFilter(QueryBuilder &$queryBuilder, $value)
    {
        if ($value == 'all') {
            return;
        }
        $queryBuilder->andWhere('org.tags LIKE :tag');
        $queryBuilder->setParameter('tag', '%"'.$value.'"%');
    }

    public function addEventStartSort(QueryBuilder &$queryBuilder, $order)
    {
        $queryBuilder->addOrderBy('root.event_start', $order == '-' ? 'DESC' : 'ASC');
    }

    public function addCfpEndDateSort(QueryBuilder &$queryBuilder, $order)
    {
        $queryBuilder->addOrderBy('root.cfp_end', $order == '-' ? 'ASC' : 'DESC');
    }

    public function transformArrayResult(ApiCriteria $criteria, &$item)
    {
        parent::transformArrayResult($criteria, $item);
    }
}
