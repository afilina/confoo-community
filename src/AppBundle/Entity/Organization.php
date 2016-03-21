<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

use Sabre\VObject;

/**
 * @ORM\Entity(repositoryClass="\AppBundle\Repository\OrganizationRepository")
 * @ORM\Table(indexes={@ORM\Index(name="key_idx", columns={"unique_key"})})
 */
class Organization
{
    use \ApiBundle\Entity\AccessorTrait;

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @ORM\Column(name="unique_key", type="string", length=20)
     */
    protected $key;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $tags = [];

    /**
     * @ORM\Column(type="datetime")
     */
    protected $first_event;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $last_event = null;

    /**
     * @ORM\OneToOne(targetEntity="SpeakerKit", mappedBy="organization", cascade={"persist"})
     **/
    protected $speaker_kit;

    /**
     * @ORM\OneToMany(targetEntity="Event", mappedBy="organization", cascade={"persist"})
     **/
    protected $events;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $website = null;

    /**
     * @ORM\Column(type="string", length=20)
     * ug|conf|hackathon
     */
    protected $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $twitter = null;

    public function __construct()
    {
        $this->events = new ArrayCollection();
    }

    public function replaceWithArray(array $array)
    {
        $this->key = $array['key'];
        $this->name = $array['name'];
        $this->tags = $array['tags'];
        $this->first_event = new \DateTime($array['first_event']);
        if (!empty($array['last_event'])) {
            $this->last_event = new \DateTime($array['last_event']);
        }
        if ($this->speaker_kit == null) {
            $this->setSpeakerKit(new SpeakerKit());
        }
        if (isset($array['events'])) {
            foreach ($array['events'] as $i => $event) {
                $eventEntity = $this->events->get($i);
                if ($eventEntity == null) {
                    $eventEntity = new Event();
                    $this->addEvent($eventEntity);
                }
                $eventEntity->replaceWithArray($event);
                $eventEntity->unique_key = $this->key.'/'.$event['name'];
            }
        }
        
        $this->speaker_kit->is_unknown = true;
        if (!empty($array['speaker_kit'])) {
            $this->speaker_kit->replaceWithArray($array['speaker_kit']);
            $this->speaker_kit->is_unknown = false;
        }
        if (!empty($array['website'])) {
            $this->website = $array['website'];
        }
        if (!empty($array['twitter'])) {
            $this->twitter = $array['twitter'];
        }
    }

    public function mergeIcalData($icalData)
    {
        $vcalendar = VObject\Reader::read($icalData);
        if (count($vcalendar->VEVENT) == 0) {
            return;
        }

        $eventIndexesByKey = [];
        foreach ($this->events as $i => $event) {
            $eventIndexesByKey[$event->unique_key] = $i;
        }

        foreach ($vcalendar->VEVENT as $event) {
            // Assuming that the ical source is consistent in its UIDs.
            $eventKey = $this->key.'/'.$event->UID;
            if (isset($eventIndexesByKey[$eventKey])) {
                $eventEntity = $this->events->get($eventIndexesByKey[$eventKey]);
            } else {
                $eventEntity = new Event();
                $this->addEvent($eventEntity);
            }

            $eventEntity->unique_key = $eventKey;
            $eventEntity->name = (string)$event->SUMMARY;
            $location = (string)$event->LOCATION;
            if (empty($location)) {
                if (isset($this->locations)) {
                    $location = $this->locations[count($this->locations)-1]['name'];
                } else {
                    $location = 'Not specified';
                }
            }
            $eventEntity->location = ['name'=>$location];
            $eventEntity->event_start = new \DateTime((string)$event->DTSTART);
            $eventEntity->event_end = new \DateTime((string)$event->DTEND);
        }
    }

    public function addEvent(Event $event)
    {
        $this->events->add($event);
        $event->organization = $this;
    }

    public function setSpeakerKit(SpeakerKit $speaker_kit)
    {
        $this->speaker_kit = $speaker_kit;
        $speaker_kit->organization = $this;
    }

    public function getTypeName()
    {
        $types = [
            'conf' => 'Conference',
            'ug' => 'User group',
            'hackathon' => 'Hackathon',
        ];
        return $types[$this->type];
    }

    public function __toString()
    {
        return $this->name;
    }
}
