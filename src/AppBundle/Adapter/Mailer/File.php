<?php
namespace AppBundle\Adapter\Mailer;

class File
{
    use \ApiBundle\Entity\AccessorTrait;

    protected $path = null;
    protected $content = null;
    protected $name = null;
    protected $mimeType = null;

    public function __construct()
    {
    }
}