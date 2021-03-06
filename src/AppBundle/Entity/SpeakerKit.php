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
     * @ORM\Column(type="boolean")
     */
    protected $is_unknown = true;

    /**
     * @ORM\OneToOne(targetEntity="Organization", inversedBy="speaker_kit")
     **/
    protected $organization;

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

    public function getIncluded()
    {
        $names = [];
        $names_dict = [
            'ticket_included' => 'Conference ticket',
            'hotel_included' => 'Lodging',
            'travel_included' => 'Travel',
        ];
        if ($this->is_unknown) {
            $names = ['Unknown'];
            return $names;
        }
        foreach ($names_dict as $key => $name) {
            $value = $this->{$key};
            if ($value === true) {
                $names[] = $names_dict[$key];
            }
        }
        return $names;
    }
}
