<?php

namespace AppBundle\Controller;

use Doctrine\ORM\AbstractQuery;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use AppBundle\Adapter\Mailer as Mailer;

class AlertController extends Controller
{
    /**
     * @Route("/alert/cfp/subscribe", name="alert_cfp_subscribe")
     */
    public function cfpSubscribeAction(Request $request)
    {
        $confRepo = $this->container->get('doctrine')->getRepository('AppBundle\Entity\Conference');
        $tags = $confRepo->getTagList(new \ApiBundle\Repository\ApiCriteria(), AbstractQuery::HYDRATE_ARRAY);

        $alert = new \AppBundle\Entity\CfpAlert();
        $alertForm = $this->createForm(\AppBundle\Form\AlertType::class, $alert, [
            'action' => $this->generateUrl('alert_cfp_subscribe'),
            'tags' => $tags,
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

            try {
                $mailer = $this->container->get('app.mailer');
                $mailer->setOption('subaccount', 'community.confoo.ca');
                $message = new Mailer\Message();
                $message->subject = 'Call for Papers Subscription';
                $message->from = new Mailer\Email();
                $message->from->address = 'no-reply@confoo.ca';
                $message->from->name = 'ConFoo Community';
                $to = new Mailer\Email();
                $to->address = $alert->email;
                $message->to = [$to];
                $html = $this->container->get('templating')->render('AppBundle::Alert/cfp-alert-welcome.html.twig', [
                    'frequency' => $alert->frequency,
                    'tag' => $alert->tag,
                ]);
                $message->html = $html;

                $delivery = $mailer->executeSend($message);
            } catch (\Exception $e) {
            }

            $this->addFlash(
                'success',
                'Great! You just subscribed to the Call for Papers.'
            );

            return $this->redirectToRoute('alert_cfp_subscribe');
        }

        return $this->render('AppBundle::Alert/cfp-subscribe.html.twig', [
            'alertForm' => $alertForm->createView(),
        ]);
    }
}
