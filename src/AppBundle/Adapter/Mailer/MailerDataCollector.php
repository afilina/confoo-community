<?php

namespace AppBundle\Adapter\Mailer;

use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Used to collect mail activity during functional tests.
 */
class MailerDataCollector extends DataCollector
{
    protected $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    public function collect(Request $request, Response $response, \Exception $exception = null)
    {
        $this->data = $this->mailer->getLog();
    }

    public function getRequests()
    {
        return $this->data;
    }

    public function getName()
    {
        return 'zurich_api.mailer.data_collector';
    }
}