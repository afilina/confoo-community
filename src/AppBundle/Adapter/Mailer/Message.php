<?php
namespace AppBundle\Adapter\Mailer;

class Message
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $bulkId; // identifier within our system, such as a release id
    protected $subject = null;
    protected $html = null;
    protected $text = null;
    protected $from;
    protected $to = [];
    protected $headers = [];
    protected $options;
    protected $attachments = [];
    protected $sendDate;

    public function __construct()
    {
        $this->from = new Email();
        $this->options = new Options();
        $this->sendDate = null;
    }
}