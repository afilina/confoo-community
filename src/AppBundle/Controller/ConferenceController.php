<?php

namespace AppBundle\Controller;

use Doctrine\ORM\AbstractQuery;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class ConferenceController extends Controller
{
    /**
     * @Route("/{page}", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
     * @Route("/upcoming/t/{tag}/{page}", name="upcoming_list", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
     */
    public function upcomingListAction(Request $request)
    {
        $tag = $request->attributes->get('tag');
        $page = $request->attributes->get('page');

        $eventStartMin = new \DateTime();
        $eventStartMin->add(new \DateInterval('P0M'));
        $eventStartMax = new \DateTime();
        $eventStartMax->add(new \DateInterval('P1M'));

        $apiCriteria = new \ApiBundle\Repository\ApiCriteria([
            'eventStartMin' => $eventStartMin,
            'eventStartMax' => $eventStartMax,
        ]); // In the next month
        $apiCriteria->addSystemFilter('tag', $tag);
        $apiCriteria->pageNumber = $page;
        $apiCriteria->sorting = 'startDate';

        $eventRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\ConferenceEvent');
        $upcomingConfs = $eventRepo->findList($apiCriteria, AbstractQuery::HYDRATE_ARRAY);

        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $confRepo = $confRepo->findTagList(new \ApiBundle\Repository\ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);

        $tags = [];
        foreach ($confRepo['data'] as $conf) {
            $tags = array_merge($tags, $conf['tags']);
        }
        $tags = array_unique($tags);
        sort($tags);

        $pages = [];
        for ($i=0; $i < $upcomingConfs['meta']['pages']; $i++) { 
            $pages[] = $i+1;
        }

        return $this->render('AppBundle::Conference/upcoming-list.html.twig', [
            'upcomingConfs' => $upcomingConfs['data'],
            'tags' => $tags,
            'tag' => $tag,
            'pages' => $pages,
            'page' => $page,
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
        $apiCriteria->pageNumber = $page;
        $eventRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\ConferenceEvent');
        $openCfps = $eventRepo->findList($apiCriteria, AbstractQuery::HYDRATE_ARRAY);

        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $confRepo = $confRepo->findTagList(new \ApiBundle\Repository\ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);

        $tags = [];
        foreach ($confRepo['data'] as $conf) {
            $tags = array_merge($tags, $conf['tags']);
        }
        $tags = array_unique($tags);
        sort($tags);

        $pages = [];
        for ($i=0; $i < $openCfps['meta']['pages']; $i++) { 
            $pages[] = $i+1;
        }

        return $this->render('AppBundle::Conference/cfp-list.html.twig', [
            'openCfps' => $openCfps['data'],
            'tags' => $tags,
            'tag' => $tag,
            'pages' => $pages,
            'page' => $page,
        ]);
    }

    /**
     * @Route("/conferences/t/{tag}/{page}", name="conferences_list", defaults={"page": 1, "tag": "all"}, requirements={"page": "\d+"})
     */
    public function conferencesListAction(Request $request)
    {
        $tag = $request->attributes->get('tag');
        $page = $request->attributes->get('page');

        $apiCriteria = new \ApiBundle\Repository\ApiCriteria([
        ]); // All
        $apiCriteria->addSystemFilter('tag', $tag);
        $apiCriteria->pageNumber = $page;

        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $allConfs = $confRepo->findList($apiCriteria, AbstractQuery::HYDRATE_ARRAY);
        $confTags = $confRepo->findTagList(new \ApiBundle\Repository\ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);

        $tags = [];
        foreach ($confTags['data'] as $conf) {
            $tags = array_merge($tags, $conf['tags']);
        }
        $tags = array_unique($tags);
        sort($tags);

        $pages = [];
        for ($i=0; $i < $allConfs['meta']['pages']; $i++) { 
            $pages[] = $i+1;
        }

        return $this->render('AppBundle::Conference/conferences-list.html.twig', [
            'allConfs' => $allConfs['data'],
            'tags' => $tags,
            'tag' => $tag,
            'pages' => $pages,
            'page' => $page,
        ]);
    }
}
