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
    private $orgRepo;

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
        $output->getFormatter()->setStyle('err', new OutputFormatterStyle('black', 'red'));

        $this->orgRepo = $this->getContainer()->get('doctrine')->getRepository('AppBundle\Entity\Organization');

        $this->executeOnce();
    }

    public function loadConferences()
    {
        `wget https://github.com/afilina/dev-community-data/archive/master.zip`;
        `unzip master.zip -d tmp`;
        chdir('tmp/dev-community-data-master/data/conferences');
        $fileNames = glob('*.json');
        return $fileNames;
    }

    public function cleanup()
    {
        $dir = __DIR__.'/../../../';
        chdir($dir);
        `rm -R tmp`;
        `rm master.zip`;
    }

    public function executeOnce()
    {
        $dir = __DIR__.'/../../../tmp/dev-community-data-master/data/conferences';
        $fileNames = $this->loadConferences();

        // Merge with database
        $batchSize = 50;
        $batchI = 0;
        $em = $this->getContainer()->get('doctrine.orm.entity_manager');

        foreach ($fileNames as $fileName) {
            $conference = json_decode(file_get_contents($dir.'/'.$fileName), true);
            $conference['key'] = preg_replace('/.json/', '', $fileName);

            $apiCriteria = new ApiCriteria(['key' => $conference['key']]);
            $orgEntity = $this->orgRepo->findItem($apiCriteria, 1)['data'];
            if ($orgEntity == null) {
                $orgEntity = new Entity\Organization();
            }

            $orgEntity->replaceWithArray($conference);
            $orgEntity->type = 'conf';
            $em->persist($orgEntity);
            if (($batchI % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
            ++$batchI;
        }
        $em->flush();

        $this->cleanup();
    }
}
