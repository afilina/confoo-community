<?php

namespace AppBundle\Controller;

use Doctrine\ORM\AbstractQuery;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use GuzzleHttp\Client;

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
            'eventEndMin' => new \DateTime(), // skip past events
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
            if (!empty($searchData['min_date'])) {
                $apiCriteria->addUserFilter('eventStartMin', $searchData['min_date']);
            }
            if (!empty($searchData['max_date'])) {
                $apiCriteria->addUserFilter('eventEndMax', $searchData['max_date']);
            }
            if (!empty($searchData['cfp_status'])) {
                $apiCriteria->addUserFilter('cfpStatus', $searchData['cfp_status']);
            }
            if (!empty($searchData['location'])) {

                if (empty($searchData['radius'])) {
                    $searchData['radius'] = 25;
                }

                $api_key = $this->container->getParameter('google_api_key');
                $client = new Client();
                $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($searchData['location']).'&key='.$api_key;
                $response = $client->request('GET', $url, [
                    'timeout' => 2.0,
                ]);
                $body = $response->getBody()->getContents();
                // $body = '{
                //    "results" : [
                //       {
                //          "geometry" : {
                //             "location" : {
                //                "lat" : 45.5016889,
                //                "lng" : -73.567256
                //             }
                //          }
                //       }
                //    ]
                // }
                // ';
                $json = json_decode($body, true);
                $data = $json['results'][0];

                $apiCriteria->addUserFilter('nearLocation', [
                    'latitude' => $data['geometry']['location']['lat'],
                    'longitude' => $data['geometry']['location']['lng'],
                    'radius' => $searchData['radius'],
                    'unit' => 'km',
                ]);

                // TODO: handle invalid location
            }
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
