<?php
namespace AppBundle\Adapter\Mailer;

class Email
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $address = null;
    protected $name = null;
    protected $type = null;

    public function __construct($type = 'to')
    {
        $this->type = $type;
    }
}