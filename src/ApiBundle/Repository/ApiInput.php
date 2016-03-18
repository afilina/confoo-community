<?php
namespace ApiBundle\Repository;

class ApiInput
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $files = [];
    protected $data = [];

    public function setJsonBody($body)
    {
        $array = json_decode($body, true);
        if ($array == null || !array_key_exists('data', $array)) {
            throw new \Exception('Body must contain "data" property.');
        }
        $this->data = $array['data'];
    }
}
