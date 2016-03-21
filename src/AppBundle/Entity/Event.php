<?php
namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="\AppBundle\Repository\EventRepository")
 * @ORM\Table()
 */
class Event
{
    use \ApiBundle\Entity\AccessorTrait;

    /**
    * @ORM\Id
    * @ORM\Column(type="integer")
    * @ORM\GeneratedValue(strategy="AUTO")
    */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="events")
     **/
    protected $organization;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $unique_key;

    /**
     * @ORM\Column(type="string", length=255)
     */
    protected $name;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $location = [];

    /**
     * @ORM\Column(type="datetime")
     */
    protected $event_start;

    /**
     * @ORM\Column(type="datetime")
     */
    protected $event_end;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $cfp_start = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $cfp_end = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $cfs_start = null;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    protected $cfs_end = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $session_feed = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    protected $speaker_feed = null;

    /**
     * @ORM\Column(type="string", length=16, nullable=true)
     */
    protected $hashtag = null;

    /**
     * @ORM\Column(type="json_array")
     */
    protected $organizers = [];

    /**
     * @ORM\Column(type="decimal", precision=9, scale=6, nullable=true)
     */
    protected $latitude = null;

    /**
     * @ORM\Column(type="decimal", precision=9, scale=6, nullable=true)
     */
    protected $longitude = null;

    public function getCfpStatus()
    {
        $now = time();
        if ($this->cfp_end == null) {
            return [
                'name' => 'Unknown',
                'key' => 'closed',
            ];
        }
        if ($this->cfp_end->getTimestamp() < $now) {
            return [
                'name' => 'Closed',
                'key' => 'closed',
            ];
        }
        if ($this->cfp_start->getTimestamp() <= $now && $this->cfp_end->getTimestamp() >= $now) {
            return [
                'name' => 'Open',
                'key' => 'open',
            ];
        }
        if ($this->cfp_start->getTimestamp() > $now) {
            return [
                'name' => 'Not open yet',
                'key' => 'upcoming',
            ];
        }
    }

    public function hasEnded()
    {
        return $this->event_end->getTimestamp() < time();
    }

    public function replaceWithArray(array $array)
    {
        $this->name = $array['name'];
        $this->location = $array['location'];
        $this->event_start = new \DateTime($array['event_start']);
        $this->event_end = new \DateTime($array['event_end']);

        if (!empty($array['cfp_start'])) {
            $this->cfp_start = new \DateTime($array['cfp_start']);
        }
        if (!empty($array['cfp_end'])) {
            $this->cfp_end = new \DateTime($array['cfp_end']);
        }
        if (!empty($array['hashtag'])) {
            $this->hashtag = $array['hashtag'];
        }
    }
}
