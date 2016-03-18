<?php
namespace ApiBundle\Repository;

use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

abstract class AbstractRepository extends EntityRepository
{
    static $uploadRoot;
    
    public function transformArrayResult(ApiCriteria $criteria, &$result)
    {
        if (isset($result['item'])) {
            $item = $result['item'];
            // Doctrine has hydration limitations, so non-relationships end up in the outer array.
            // We fix that.
            foreach ($result as $property => $value) {
                if (!in_array($property, ['item'])) {
                    $item[$property] = $value;
                }
            }
        } else {
            $item = $result;
        }
        
        // Correctly cast dates and expected integers.
        array_walk_recursive($item, function(&$value, $property)
        {
            if ($value instanceof \DateTime) {
                $value = $value->format('Y-m-d H:i:s');
                return;
            }
            if (substr($property, 0, 3) == 'num') {
                $value = (int)$value;
                return;
            }
            if ($property === 'path') {
                $value = $this::$uploadRoot.$value;
                return;
            }
        });
        $result = $item;
    }

    public function getRawList($sql, $params = [])
    {
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function insertRawBatch($sql, $values = [], $useUuid = false)
    {
        $flatValues = [];
        foreach ($values as $row) {
            foreach ($row as $col) {
                $flatValues[] = $col;
            }
        }

        $uuid = $useUuid ? 'UUID(),' : '';

        $placeholders_array = array_fill(0, count($values[0]), '?');
        $placeholders_string = '('.$uuid.join(',', $placeholders_array).')';
        $placeholders_array = array_fill(0, count($values), $placeholders_string);
        $placeholders_string = join(',', $placeholders_array);
        $sql = str_replace('?', $placeholders_string, $sql);

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->execute($flatValues);
    }

    public function addFilterJoins(QueryBuilder &$queryBuilder)
    {
    }

    abstract function findList(ApiCriteria $criteria, $hydration = 2);
    abstract function findItem(ApiCriteria $criteria, $hydration = 2);
    abstract function saveItem($item, $immediate = true);
    abstract function deleteItem($item, $immediate = true);
}