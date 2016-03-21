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

        $this->eventRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\Event');

        $this->executeOnce();
    }

    public function executeOnce()
    {
        // Fetch and parse JSON
        $api_key = $this->getContainer()->getParameter('google_api_key');
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

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
            $url = 'https://maps.googleapis.com/maps/api/geocode/json?address='.urlencode($event['location']['name']).'&key='.$api_key;
            $response = $client->request('GET', $url, [
                'timeout' => 2.0,
            ]);
            $body = $response->getBody()->getContents();
            // $body = '{
            //    "results" : [
            //       {
            //          "address_components" : [
            //             {
            //                "long_name" : "Montreal",
            //                "short_name" : "Montreal",
            //                "types" : [ "locality", "political" ]
            //             },
            //             {
            //                "long_name" : "Montreal",
            //                "short_name" : "Montreal",
            //                "types" : [ "administrative_area_level_2", "political" ]
            //             },
            //             {
            //                "long_name" : "Québec",
            //                "short_name" : "QC",
            //                "types" : [ "administrative_area_level_1", "political" ]
            //             },
            //             {
            //                "long_name" : "Canada",
            //                "short_name" : "CA",
            //                "types" : [ "country", "political" ]
            //             }
            //          ],
            //          "formatted_address" : "Montreal, QC, Canada",
            //          "geometry" : {
            //             "bounds" : {
            //                "northeast" : {
            //                   "lat" : 45.7056146,
            //                   "lng" : -73.4752355
            //                },
            //                "southwest" : {
            //                   "lat" : 45.4146367,
            //                   "lng" : -73.947824
            //                }
            //             },
            //             "location" : {
            //                "lat" : 45.5016889,
            //                "lng" : -73.567256
            //             },
            //             "location_type" : "APPROXIMATE",
            //             "viewport" : {
            //                "northeast" : {
            //                   "lat" : 45.7056146,
            //                   "lng" : -73.4752355
            //                },
            //                "southwest" : {
            //                   "lat" : 45.4146367,
            //                   "lng" : -73.947824
            //                }
            //             }
            //          },
            //          "place_id" : "ChIJDbdkHFQayUwR7-8fITgxTmU",
            //          "types" : [ "locality", "political" ]
            //       }
            //    ],
            //    "status" : "OK"
            // }
            // ';
            $json = json_decode($body, true);
            $data = $json['results'][0];

            // Reformat the location since we already have the new address
            // $event->location['name'] = $data['address_components']['formatted_address'];

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
    }
}
