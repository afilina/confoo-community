<?php
namespace AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputArgument;

use Doctrine\ORM\AbstractQuery;
use GuzzleHttp\Client;

use ApiBundle\Repository\ApiCriteria;
use AppBundle\Entity as Entity;
use AppBundle\Adapter\Mailer as Mailer;

class SendAlertsCommand extends ContainerAwareCommand
{
    private $conn;
    private $cfpAlertRepo;
    private $mail;

    protected function configure()
    {
        $this
            ->setName('alerts:send')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Bootstrap
        $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('black', 'green'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));

        $this->cfpAlertRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\CfpAlert');
        $this->mailer = $this->getContainer()->get('app.mailer');
        $this->mailer->setOption('subaccount', 'community.confoo.ca');

        $this->executeOnce();
    }

    public function executeOnce()
    {
        $apiCriteria = new ApiCriteria(['enabled' => true]);
        $cfpAlerts = $this->cfpAlertRepo->findList($apiCriteria, 1)['data'];

        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $now = new \DateTime();

        foreach ($cfpAlerts as $alert) {
            switch ($alert->frequency) {
                case 'daily':
                    $diff = 3600 * 24;
                    break;
                case 'weekly':
                    $diff = 3600 * 24 * 7;
                    break;
            }
            // Has enough time elapsed?
            if ($now->getTimestamp() - ($alert->last_alert_date->getTimestamp()) >= $diff) {
                $message = new Mailer\Message();
                $message->subject = 'Call for Papers Alert';
                $message->from = new Mailer\Email();
                $message->from->address = 'no-reply@confoo.ca';
                $message->from->name = 'ConFoo Community';
                $to = new Mailer\Email();
                $to->address = $alert->email;
                $message->to = [$to];

                // Find all CFPs that opened between last alert and now.
                $apiCriteria = new \ApiBundle\Repository\ApiCriteria([
                    'cfpStartMin' => $alert->last_alert_date,
                    'cfpStartMax' => $now,
                ]);
                $apiCriteria->addSystemFilter('tag', $alert->tag);
                $apiCriteria->allowIgnorePagination = true;
                $apiCriteria->sorting = 'eventStart';
                
                $eventRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\ConferenceEvent');
                $newCfps = $eventRepo->findList($apiCriteria, AbstractQuery::HYDRATE_ARRAY);
                if (count($newCfps['data']) == 0) {
                    continue;
                }

                $html = $this->getContainer()->get('templating')->render('AppBundle::Alert/cfp-alert.html.twig', [
                    'frequency' => $alert->frequency,
                    'tag' => $alert->tag,
                    'newCfps' => $newCfps['data'],
                ]);
                $message->html = $html;

                try {
                    $delivery = $this->mailer->executeSend($message);
                } catch (\Exception $e) {
                }

                $alert->last_alert_date = new \DateTime();
                $em->persist($alert);
                $em->flush();
                $em->clear();
            }
        }
    }
}
