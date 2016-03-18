<?php
namespace AppBundle\Adapter\Mailer;

interface MailableInterface
{
    function getMailMessage();
    function getMailRecipients();
}
