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

class FetchCoordinatesCommand extends ContainerAwareCommand
{
    private $conn;
    private $eventRepo;

    protected function configure()
    {
        $this
            ->setName('coordinates:fetch')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Bootstrap
        $output->getFormatter()->setStyle('ok', new OutputFormatterStyle('black', 'green'));
        $output->getFormatter()->setStyle('warn', new OutputFormatterStyle('black', 'yellow'));
        $output->getFormatter()->setStyle('err', new OutputFormatterStyle('black', 'red'));

        $this->eventRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\Event');

        $this->executeOnce($output);
    }

    public function executeOnce($output)
    {
        // Fetch and parse JSON
        $apiKey = $this->getContainer()->getParameter('google_api_key');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');
        $cacheManager = $this->getContainer()->get('app.cache');

        $client = new Client();

        $apiCriteria = new ApiCriteria(['hasCoords' => false]);
        $apiCriteria->allowIgnorePagination = true;
        $apiCriteria->pageSize = 0;
        $events = $this->eventRepo->findList($apiCriteria, 2)['data'];

        // Google limits 10 queries per second
        $batchSize = 10;
        $batchI = 0;

        foreach ($events as $event) {
            // Geocode via Google
            $searches = [];
            $searches[] = $event['location']['name'];
            if (count($event['organization']['locations']) == 1) {
                $searches[] = $event['organization']['locations'][0]['name'];
            }
            $data = null;

            foreach ($searches as $search) {

                if ($search == 'Not specified') {
                    continue;
                }

                $body = $cacheManager->remember('googlemaps', strtolower($search), function() use ($search, $apiKey, $client, $output) {
                    $output->writeln("Fetching coordinates for {$search}...");
                    $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($search).'&key='.$apiKey;
                    $response = $client->request('GET', $url, [
                        'timeout' => 2.0,
                    ]);
                    $body = $response->getBody()->getContents();
                    // Compact json response
                    $body = json_decode($body, true);
                    $body = json_encode($body);
                    return $body;
                });

                $json = json_decode($body, true);
                if (!isset($json['results']) || count($json['results']) == 0) {
                    $output->writeln("<err>Could not fetch coordinates for {$search}.</err>");
                    continue;
                }
                $data = $json['results'][0];
                return $data;
            }

            if ($data == null) {
                // $output->writeln("<err>Could not fetch coordinates for \"{$event['name']}\".</err>");
                continue;
            }

            // Reformat the location since we already have the new address
            // $event->location['name'] = $data['address_components']['formatted_address'];
            $output->writeln("Saving coordinates for \"{$event['name']}\"...");
            $sql = 'UPDATE event SET latitude = :lat, longitude = :lng WHERE id = :id';
            $stmt = $em->getConnection()->prepare($sql);
            $stmt->bindParam(':lat', $data['geometry']['location']['lat']);
            $stmt->bindParam(':lng', $data['geometry']['location']['lng']);
            $stmt->bindParam(':id', $event['id']);
            $stmt->execute();

            if (($batchI % $batchSize) === 0) {
                sleep(1);
            }
            ++$batchI;
        }
        $output->writeln("<ok>Done</ok>");
    }
}
