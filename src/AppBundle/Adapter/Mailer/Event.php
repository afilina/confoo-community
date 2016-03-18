<?php
namespace AppBundle\Adapter\Mailer;

class Event
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $address = null;
    protected $type = null;
    protected $data = null; // additional data associated with the event
    protected $createdDate = null;

    public function __construct()
    {
    }

    public function setType($type)
    {
        $whitelist = [
            'bounce',
            'unsubscribe',
            'spam',
            'open',
            'click',
        ];
        if (!in_array($type, $whitelist)) {
            throw new \Exception('Invalid type. Can be either of these: ' . implode(', ', $whitelist));
        }

        $this->type = $type;
    }
}