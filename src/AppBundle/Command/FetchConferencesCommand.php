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

class FetchConferencesCommand extends ContainerAwareCommand
{
    private $conn;
    private $conferenceRepo;

    protected function configure()
    {
        $this
            ->setName('conferences:fetch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Bootstrap
        $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('black', 'green'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));

        $this->conferenceRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\Conference');

        $this->executeOnce();
    }

    public function executeOnce()
    {
        // Fetch and parse JSON
        $url = 'https://raw.githubusercontent.com/afilina/dev-community-data/master/data/conferences.json';
        $client = new Client();
        $response = $client->request('GET', $url, [
            'timeout' => 2.0,
        ]);
        $conferences = json_decode($response->getBody()->getContents(), true);

        // Merge with database
        $batchSize = 50;
        $batchI = 0;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        foreach ($conferences as $conference) {
            $apiCriteria = new ApiCriteria(['key' => $conference['key']]);
            $conferenceEntity = $this->conferenceRepo->findItem($apiCriteria, 1)['data'];
            if ($conferenceEntity == null) {
                $conferenceEntity = new Entity\Conference();
            }

            $conferenceEntity->replaceWithArray($conference);
            $em->persist($conferenceEntity);
            if (($batchI % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            ++$batchI;
        }
        $em->flush();
    }
}
