<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="\AppBundle\Repository\CfpAlertRepository")
 * @ORM\Table()
 */
class CfpAlert
{
    use \ApiBundle\Entity\AccessorTrait;

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $tag;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $email;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $token;

    /**
     * @ORM\Column(type="string", length=12)
     */
    protected $frequency;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $is_enabled = 1;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $last_alert_date;

    public function __construct()
    {
    }
}
