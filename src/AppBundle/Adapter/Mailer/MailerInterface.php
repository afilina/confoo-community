<?php
namespace AppBundle\Adapter\Mailer;

interface MailerInterface
{
    function executeSend(Message $message);
    function executeCancel(Delivery $delivery);
    function executeStats(Delivery $delivery);
    function setOption($key, $value);
    function getOption($key);
}
