<?php
namespace ApiBundle\Repository;

class ApiCriteria
{
    use \ApiBundle\Entity\AccessorTrait;

    const PAGE_SIZE_DEFAULT = 10;
    const PAGE_SIZE_MAX = 25;

    protected $systemFilters = [];
    protected $userFilters = [];
    protected $sorting = [];
    protected $pageSize = self::PAGE_SIZE_DEFAULT;
    protected $pageNumber = 1;
    // This should be only set when the criteria is constructed directly by the application.
    // Example: fetching all recipients for a release before mailing.
    protected $allowIgnorePagination = false;
    protected $idsOnly = false;

    public function __construct(array $systemFilters = null, array $userFilters = null, $idsOnly = false)
    {
        if ($systemFilters) {
            $this->systemFilters = $systemFilters;
        }
        if ($userFilters) {
            $this->userFilters = $userFilters;
        }
        $this->idsOnly = $idsOnly;
    }

    public function getPageSize()
    {
        if ($this->pageSize == 0) {
            if ($this->allowIgnorePagination) {
                return 0;
            }
            return self::PAGE_SIZE_DEFAULT;
        }
        return min(self::PAGE_SIZE_MAX, $this->pageSize);
    }

    public function getPageNumber()
    {
        if ($this->getPageSize() == 0) {
            return 1;
        }
        return $this->pageNumber;
    }

    /**
     * Separate with commas. Prefix each item with plus or minus for order. No prefix = plus.
     * Examples:
     *   sort=date,-headline
     *   sort=+date
     */
    public function setSorting($value)
    {
        $parts = explode(',', $value);
        foreach ($parts as $part) {
            preg_match_all('/^(\-)?([\w]+)$/', $part, $matches);
            if (empty($matches[0])) {
                throw new \Exception('Invalid sort format.', 1);
            }
            $name = $matches[2][0];
            $order = $matches[1][0];
            $this->sorting[$name] = $order;
        }
    }

    public function addUserFilter($name, $value)
    {
        $this->userFilters[$name] = $value;
    }

    public function addSystemFilter($name, $value)
    {
        $this->systemFilters[$name] = $value;
    }

    public function getFilters()
    {
        return $this->userFilters + $this->systemFilters;
    }

    public function getFilter($key, $default = null)
    {
        $filters = $this->getFilters();
        if (!isset($filters[$key])) {
            if ($default === null) {
                throw new \Exception("Filter {$key} is not in the ApiCriteria");
            }
            return $default;
        }
        return $filters[$key];
    }

    public function toArray()
    {
        return [
            'filters' => $this->userFilters,
            'sorting' => $this->sorting,
            'pageSize' => (int)$this->pageSize,
            'pageNumber' => (int)$this->pageNumber,
        ];
    }
}
