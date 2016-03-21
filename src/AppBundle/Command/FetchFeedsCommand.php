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

// This should be executed hourly or less often.
class FetchFeedsCommand extends ContainerAwareCommand
{
    private $conn;
    private $orgRepo;

    protected function configure()
    {
        $this
            ->setName('feeds:fetch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Bootstrap
        $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('black', 'green'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));

        $this->orgRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\Organization');

        $this->executeOnce();
    }

    public function executeOnce()
    {
        // Fetch and parse JSON
        $url = 'https://raw.githubusercontent.com/afilina/dev-community-data/master/data/user-groups.json';
        $client = new Client();
        $response = $client->request('GET', $url, [
            'timeout' => 2.0,
        ]);
        $userGroups = json_decode($response->getBody()->getContents(), true);

        // Merge with database
        $batchSize = 10;
        $batchI = 0;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = new Client();

        foreach ($userGroups as $userGroup) {
            $apiCriteria = new ApiCriteria(['key' => $userGroup['key']]);
            $orgEntity = $this->orgRepo->findItem($apiCriteria, 1)['data'];
            if ($orgEntity == null) {
                continue;
            }

            if (isset($userGroup['calendar_feed']) && !empty($userGroup['calendar_feed'])) {
                // Only parse .ical for now
                if (in_array(substr($userGroup['calendar_feed'], -5), ['.ical', '/ical', 'ical/'])) {
                    $response = $client->request('GET', $userGroup['calendar_feed'], [
                        'timeout' => 2.0,
                    ]);
                    $icalData = $response->getBody()->getContents();
                    $orgEntity->mergeIcalData($icalData);
                }
            }

            $em->persist($orgEntity);

            if (($batchI % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            ++$batchI;
        }
        $em->flush();
    }
}
