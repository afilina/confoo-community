<?php

namespace AppBundle\Controller;

use Doctrine\ORM\AbstractQuery;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConferenceController extends Controller
{
    /**
     * @Route("/conferences/t/{tag}/{page}", name="conferences_list", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
     */
    public function conferencesListAction(Request $request)
    {
        $tag = $request->attributes->get('tag');
        $page = $request->attributes->get('page');

        $searchForm = $this->createForm(\AppBundle\Form\EventSearchType::class, null, [
            'action' => $this->generateUrl('events_list', ['tag' => $tag, 'page' => 1]),
            'method' => 'GET',
            'csrf_protection' => false,
        ]);

        $searchForm->handleRequest($request);

        $apiCriteria = new \ApiBundle\Repository\ApiCriteria([
        ]); // All
        $apiCriteria->addSystemFilter('tag', $tag);
        $apiCriteria->pageNumber = $page;

        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $allConfs = $confRepo->findList($apiCriteria, AbstractQuery::HYDRATE_OBJECT);
        $tags = $confRepo->getTagList(new \ApiBundle\Repository\ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);

        $pages = [];
        for ($i=0; $i < $allConfs['meta']['pages']; $i++) { 
            $pages[] = $i+1;
        }

        return $this->render('AppBundle::Conference/conference-list.html.twig', [
            'allConfs' => $allConfs['data'],
            'tags' => $tags,
            'tag' => $tag,
            'pages' => $pages,
            'page' => $page,
            'searchForm' => $searchForm->createView(),
            'queryString' => $request->getQueryString(),
        ]);
    }
}
