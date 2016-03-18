<?php
namespace AppBundle\Adapter\Mailer;

class Delivery
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $messageIds = null;
    protected $numSent = 0;
    protected $numBounced = 0;
    protected $numUnsubscribed = 0;
    protected $numOpened = 0;
    protected $numClicked = 0;
    protected $stats = null;
    protected $scheduledDate = null;
    protected $sender = null;
    protected $bulkId = null;

    public function __construct()
    {
        $this->messageIds = [];
    }
}