<?php
namespace AppBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;

use AppBundle\Entity as Entity;

use ApiBundle\Repository\AbstractRepository;
use ApiBundle\Repository\RepositoryInterface;
use ApiBundle\Repository\ApiCriteria;
use ApiBundle\Repository\ApiQuery;

class OrganizationRepository extends AbstractRepository
{
    private $name = 'organizaiton';

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
            ->select('PARTIAL root.{id, key, name, website, twitter, type} AS item')
            ->addSelect('event')
            ->leftJoin('root.events', 'event')
            ->addSelect('speaker_kit')
            ->leftJoin('root.speaker_kit', 'speaker_kit')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $result = $apiQuery->queryItem($hydration);
        return $result;
    }

    public function findList(ApiCriteria $criteria, $hydration = 2)
    {
        $query = $this
            ->createQueryBuilder('root')
            ->select('PARTIAL root.{id, key, name, website, twitter, tags, type} AS item')
            ->addSelect('PARTIAL event.{id, location, event_start, event_end, cfp_start, cfp_end}')
            ->leftJoin('root.events', 'event')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $results = $apiQuery->queryList($hydration);
        return $results;
    }

    public function findTagList(ApiCriteria $criteria, $hydration = 2)
    {
        $query = $this
            ->createQueryBuilder('root')
            ->select('PARTIAL root.{id, tags} AS item')
        ;
        $apiQuery = new ApiQuery($this, $query, $criteria);

        $results = $apiQuery->queryList($hydration);
        return $results;
    }

    public function getTagList()
    {
        $tagList = $this->findTagList(new ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);
        $tags = [];
        foreach ($tagList['data'] as $org) {
            $tags = array_merge($tags, $org['tags']);
        }
        $tags = array_unique($tags);
        sort($tags);
        return $tags;
    }

    public function addIdFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.id = :id');
        $queryBuilder->setParameter('id', $value);
    }

    public function addKeyFilter(QueryBuilder &$queryBuilder, $value)
    {
        $queryBuilder->andWhere('root.key = :key');
        $queryBuilder->setParameter('key', $value);
    }

    public function addTagFilter(QueryBuilder &$queryBuilder, $value)
    {
        if ($value == 'all') {
            return;
        }
        $queryBuilder->andWhere('root.tags LIKE :tag');
        $queryBuilder->setParameter('tag', '%"'.$value.'"%');
    }

    public function transformArrayResult(ApiCriteria $criteria, &$item)
    {
        parent::transformArrayResult($criteria, $item);
    }
}
