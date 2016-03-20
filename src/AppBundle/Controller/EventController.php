<?php

namespace AppBundle\Controller;

use Doctrine\ORM\AbstractQuery;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class EventController extends Controller
{
    /**
     * @Route("/events/t/{tag}/{page}", name="events_list", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
     */
    public function listAction(Request $request)
    {
        $tag = $request->attributes->get('tag');
        $page = $request->attributes->get('page');

        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $eventRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\ConferenceEvent');

        $apiCriteria = new \ApiBundle\Repository\ApiCriteria([
            'tag' => $tag,
            'eventEndMax' => new \DateTime(), // skip past events
        ]);
        $apiCriteria->pageNumber = $page;
        $apiCriteria->sorting = 'startDate';

        // Search form
        $searchForm = $this->createForm(\AppBundle\Form\EventSearchType::class, null, [
            'action' => $this->generateUrl('events_list', ['tag' => $tag, 'page' => 1]),
            'method' => 'GET',
            'csrf_protection' => false,
        ]);

        $searchForm->handleRequest($request);
        if ($searchForm->isValid()) {
            $searchData = $searchForm->getData();
            $apiCriteria->addUserFilter('cfpStatus', $searchData['cfp_status']);
        }

        // Query
        $events = $eventRepo->findList($apiCriteria, AbstractQuery::HYDRATE_OBJECT);
        $tags = $confRepo->getTagList(new \ApiBundle\Repository\ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);

        $pages = [];
        for ($i=0; $i < $events['meta']['pages']; $i++) { 
            $pages[] = $i+1;
        }

        return $this->render('AppBundle::Event/event-list.html.twig', [
            'events' => $events['data'],
            'tags' => $tags,
            'tag' => $tag,
            'pages' => $pages,
            'page' => $page,
            'searchForm' => $searchForm->createView(),
            'queryString' => $request->getQueryString(),
        ]);
    }
}
