<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
 * @ORM\Table()
 */
class SpeakerKit
{
    use \ApiBundle\Entity\AccessorTrait;

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $ticket_included = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $hotel_included = false;

    /**
     * @ORM\Column(type="boolean")
     */
    protected $travel_included = false;

    /**
     * @ORM\OneToOne(targetEntity="Conference", inversedBy="speaker_kit")
     **/
    protected $conference;

    public function replaceWithArray(array $array)
    {
        if (isset($array['ticket_included'])) {
            $this->ticket_included = $array['ticket_included'];
        }
        if (isset($array['hotel_included'])) {
            $this->hotel_included = $array['hotel_included'];
        }
        if (isset($array['travel_included'])) {
            $this->travel_included = $array['travel_included'];
        }        
    }
}
