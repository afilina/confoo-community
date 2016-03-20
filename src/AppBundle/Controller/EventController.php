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
     * @Route("/{page}", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
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
                    $searchForm->get('radius')->addError(new \Symfony\Component\Form\FormError('Using default radius of 25 km.'));
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

                if (count($json['results']) > 0) {

                    $data = $json['results'][0];
                    $apiCriteria->addUserFilter('nearLocation', [
                        'latitude' => $data['geometry']['location']['lat'],
                        'longitude' => $data['geometry']['location']['lng'],
                        'radius' => $searchData['radius'],
                        'unit' => 'km',
                    ]);
                } else {
                    $searchForm->get('location')->addError(new \Symfony\Component\Form\FormError('Couldn\'t find location. Ignoring this filter.'));
                }
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

    /**
     * @Route("/cfp/t/{tag}/{page}", name="cfp_list", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
     */
    public function cfpListAction(Request $request)
    {
        $tag = $request->attributes->get('tag');
        $page = $request->attributes->get('page');

        $apiCriteria = new \ApiBundle\Repository\ApiCriteria([
            'cfpStart' => new \DateTime(),
            'cfpEnd' => new \DateTime(),
        ]); // Currently open
        $apiCriteria->addSystemFilter('tag', $tag);
        $apiCriteria->sorting = 'cfpEndDate';
        $apiCriteria->pageNumber = $page;
        $eventRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\ConferenceEvent');
        $openCfps = $eventRepo->findList($apiCriteria, AbstractQuery::HYDRATE_OBJECT);

        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $tags = $confRepo->getTagList();

        $pages = [];
        for ($i=0; $i < $openCfps['meta']['pages']; $i++) { 
            $pages[] = $i+1;
        }

        $alert = new \AppBundle\Entity\CfpAlert();
        $alert->tag = $tag;
        $alertForm = $this->createForm(\AppBundle\Form\AlertType::class, $alert, [
            'action' => $this->generateUrl('alert_cfp_subscribe'),
            'tags' => $tags,
        ]);

        return $this->render('AppBundle::Event/cfp-list.html.twig', [
            'openCfps' => $openCfps['data'],
            'tags' => $tags,
            'tag' => $tag,
            'pages' => $pages,
            'page' => $page,
            'alertForm' => $alertForm->createView(),
        ]);
    }
}
