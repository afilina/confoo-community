<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="\AppBundle\Repository\CacheRepository")
 * @ORM\Table()
 */
class Cache
{
    use \ApiBundle\Entity\AccessorTrait;

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @ORM\Column(type="string", length=20)
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $cache_key;

    /**
     * @ORM\Column(type="text")
     */
    protected $value;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $last_update;
}
