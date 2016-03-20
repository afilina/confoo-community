<?php
namespace ApiBundle\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\AbstractQuery;

/**
 * Puts the query together by generating filter, sort and page criteria.
 * Also managers limits with joins and gets the total count in a paginated context.
 */
class ApiQuery
{
    protected $queryBuilder;
    protected $repository;
    protected $apiCriteria;

    public function __construct(EntityRepository $repository, QueryBuilder $queryBuilder, ApiCriteria $apiCriteria)
    {
        $this->queryBuilder = $queryBuilder;
        $this->repository = $repository;
        $this->apiCriteria = $apiCriteria;
    }

    public function queryItem($hydration)
    {
        $queryBuilder = $this->queryBuilder;
        $this->addFilterCriteria($queryBuilder);
        $result = $queryBuilder->getQuery()->getOneOrNullResult($hydration);
        if ($result) {
            if ($hydration == AbstractQuery::HYDRATE_ARRAY) {
                $this->repository->transformArrayResult($this->apiCriteria, $result);
            } else {
                $result = $result['item'];
            }
        }
        return [
            'data' => $result,
            'meta' => [
                'filters' => $this->apiCriteria->userFilters
            ]
        ];
    }

    public function queryList($hydration)
    {
        // Count
        $queryBuilder = $this->repository
            ->createQueryBuilder('root')
            ->select('COUNT(root.id) AS num')
            ->groupBy('root.id')
        ;
        $this->repository->addFilterJoins($queryBuilder);
        $this->addFilterCriteria($queryBuilder);
        // var_dump($queryBuilder->getQuery()->getSql(), $queryBuilder->getQuery()->getParameters());exit;
        $count = count($queryBuilder->getQuery()->getResult(AbstractQuery::HYDRATE_ARRAY));

        if ($count == 0) {
            return [
                'data' => [],
                'meta' => [
                    'count' => 0,
                    'pages' => 0,
                ] + $this->apiCriteria->toArray(),
            ];
        }

        // Get ids
        $queryBuilder = $this->repository
            ->createQueryBuilder('root')
            ->select('root.id')
            ->distinct('root.id')
        ;
        $this->repository->addFilterJoins($queryBuilder);
        $this->addFilterCriteria($queryBuilder);
        $this->addSortCriteria($queryBuilder);
        $this->addPageCriteria($queryBuilder);
        $ids = $queryBuilder->getQuery()->getScalarResult();

        // Provide 3rd argument to ApiCriteria to only return an array of matching ids.
        if ($this->apiCriteria->idsOnly) {
            $response = [];
            foreach ($ids as $item) {
                $response[] = $item['id'];
            }
            return ['data' => $response];
        }

        // Get the data (filtering and pagination already done in previous query)
        $queryBuilder = $this->queryBuilder
            ->andWhere('root.id IN (:ids)')
            ->setParameter('ids', $ids)
        ;
        $this->addSortCriteria($queryBuilder);
        $results = $queryBuilder->getQuery()->getResult($hydration);
        foreach ($results as &$result) {
            if ($hydration == AbstractQuery::HYDRATE_ARRAY) {
                $this->repository->transformArrayResult($this->apiCriteria, $result);
            } else {
                $result = $result['item'];
            }
        }
        return [
            'data' => $results,
            'meta' => [
                'count' => $count,
                'pages' => $this->apiCriteria->pageSize === 0 ? 1 : ceil($count/$this->apiCriteria->pageSize),
            ] + $this->apiCriteria->toArray(),
        ];
    }

    public function addFilterCriteria(QueryBuilder &$queryBuilder)
    {
        foreach ($this->apiCriteria->filters as $name => $value) {
            if ($value === '') {
                continue;
            }
            $this->repository->{'add'.ucfirst($name).'Filter'}($queryBuilder, $value);
        }
    }

    public function addSortCriteria(QueryBuilder &$queryBuilder)
    {
        foreach ($this->apiCriteria->sorting as $name => $order) {
            $this->repository->{'add'.ucfirst($name).'Sort'}($queryBuilder, $order);
        }
    }

    public function addPageCriteria(QueryBuilder &$queryBuilder)
    {
        if ($this->apiCriteria->pageSize === 0) {
            return;
        }
        $queryBuilder->setMaxResults($this->apiCriteria->pageSize);
        $queryBuilder->setFirstResult($this->apiCriteria->pageSize * ($this->apiCriteria->pageNumber - 1));
    }
}
