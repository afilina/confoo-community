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

class FetchUserGroupsCommand extends ContainerAwareCommand
{
    private $conn;
    private $orgRepo;

    protected function configure()
    {
        $this
            ->setName('usergroups:fetch')
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
        // $url = 'https://raw.githubusercontent.com/afilina/dev-community-data/master/data/user-groups.json';
        // $client = new Client();
        // $response = $client->request('GET', $url, [
        //     'timeout' => 2.0,
        // ]);
        // $userGroups = json_decode($response->getBody()->getContents(), true);
        $userGroups = json_decode(file_get_contents(__DIR__.'/user-groups.json'), true);

        // Merge with database
        $batchSize = 10;
        $batchI = 0;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $client = new Client();

        foreach ($userGroups as $userGroup) {
            $apiCriteria = new ApiCriteria(['key' => $userGroup['key']]);
            $orgEntity = $this->orgRepo->findItem($apiCriteria, 1)['data'];
            if ($orgEntity == null) {
                $orgEntity = new Entity\Organization();
            }

            $orgEntity->replaceWithArray($userGroup);
            $orgEntity->type = 'ug';

            if (isset($userGroup['calendar_feed']) && !empty($userGroup['calendar_feed'])) {
                // Only parse .ical for now
                if (in_array(substr($userGroup['calendar_feed'], -5), ['.ical', '/ical', 'ical/'])) {
                    // $response = $client->request('GET', $userGroup['calendar_feed'], [
                    //     'timeout' => 2.0,
                    // ]);
                    // $icalData = $response->getBody()->getContents();
                    // file_put_contents(__DIR__.'/'.$userGroup['key'].'.ical', $icalData);
                    $icalData = file_get_contents(__DIR__.'/'.$userGroup['key'].'.ical');
                    $orgEntity->mergeIcalData($icalData);
                }
            }

            $em->persist($orgEntity);

            echo 'persisting "'.$userGroup['key'].'" with '.count($orgEntity->events).' events...'."\n";
            if (($batchI % $batchSize) === 0) {
                echo 'flushing batch...'."\n";
                $em->flush();
                echo 'clearing...'."\n";
                $em->clear();
                echo 'done'."\n";
            }
            ++$batchI;
        }
        $em->flush();
    }
}
