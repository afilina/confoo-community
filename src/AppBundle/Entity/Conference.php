<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="\AppBundle\Repository\ConferenceRepository")
 * @ORM\Table(indexes={@ORM\Index(name="key_idx", columns={"unique_key"})})
 */
class Conference
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
     * @ORM\OneToOne(targetEntity="SpeakerKit", mappedBy="conference", cascade={"persist"})
     **/
    protected $speaker_kit;

    /**
     * @ORM\OneToMany(targetEntity="ConferenceEvent", mappedBy="conference", cascade={"persist"})
     **/
    protected $events;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $website;

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
        foreach ($array['events'] as $i => $event) {
            $conferenceEvent = $this->events->get($i);
            if ($conferenceEvent == null) {
                $conferenceEvent = new ConferenceEvent();
                $this->addEvent($conferenceEvent);
                
            }
            $conferenceEvent->replaceWithArray($event);
        }
        if (!empty($array['speaker_kit'])) {
            $this->speaker_kit->replaceWithArray($array['speaker_kit']);
        }
        $this->website = $array['website'];
        if (!empty($array['twitter'])) {
            $this->twitter = $array['twitter'];
        }
    }

    public function addEvent(ConferenceEvent $event)
    {
        $this->events->add($event);
        $event->conference = $this;
    }

    public function setSpeakerKit(SpeakerKit $speaker_kit)
    {
        $this->speaker_kit = $speaker_kit;
        $speaker_kit->conference = $this;
    }
}
