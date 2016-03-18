<?php

namespace AppBundle\Controller;

use Doctrine\ORM\AbstractQuery;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class AlertController extends Controller
{
    /**
     * @Route("/alert/cfp/subscribe", name="alert_cfp_subscribe")
     */
    public function cfpSubscribeAction(Request $request)
    {
        $alert = new \AppBundle\Entity\CfpAlert();
        $alertForm = $this->createForm(\AppBundle\Form\AlertType::class, $alert, [
            'action' => $this->generateUrl('alert_cfp_subscribe'),
        ]);

        $alertForm->handleRequest($request);

        if ($alertForm->isSubmitted() && $alertForm->isValid()) {
            $alert->token = password_hash(uniqid(), PASSWORD_DEFAULT);
            $alert->last_alert_date = new \DateTime();
            if ($alert->tag == null) {
                $alert->tag = 'all';
            }

            // We subtract the days to get a first alert right away.
            switch ($alert->frequency) {
                case 'daily':
                    $alert->last_alert_date->sub(new \DateInterval('P1D'));
                    break;
                case 'weekly':
                    $alert->last_alert_date->sub(new \DateInterval('P7D'));
                    break;
            }

            $em = $this->getDoctrine()->getManager();
            $em->persist($alert);
            $em->flush();

            $this->addFlash(
                'success',
                'Check your inbox! You should have received your first Call for Papers alert.'
            );

            return $this->redirectToRoute('alert_cfp_subscribe');
        }

        return $this->render('AppBundle::Alert/cfp-subscribe.html.twig', [
            'alertForm' => $alertForm->createView(),
        ]);
    }
}
