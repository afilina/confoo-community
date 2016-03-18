<?php
namespace AppBundle\Adapter\Mailer;

class Options
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $trackOpens = true;
    protected $trackClicks = true;

    public function __construct()
    {
    }
}