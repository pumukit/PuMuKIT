<?php

namespace Pumukit\OpencastBundle\Command;

use Pumukit\OpencastBundle\Services\ClientService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class MultipleOpencastHostImportCommand extends ContainerAwareCommand
{
    private $output;
    private $input;
    private $dm;
    private $opencastImportService;
    private $logger;
    private $user;
    private $password;
    private $host;
    private $force;
    private $clientService;

    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:multiple:host:import')
            ->setDescription('Import tracks from opencast passing data')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Opencast user')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Opencast password')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Path to selected tracks from PMK using regex')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<'EOT'
            
            Important:
            
            Before executing the command add Opencast URL MAPPING configuration with OC data you will access.
            
            ---------------
            
            Command to import all tracks from Opencast to PuMuKIT defining Opencast configuration
            
            <info> ** Example ( check and list ):</info>
            
            <comment>php app/console pumukit:opencast:multiple:host:import --user=myuser --password=mypassword --host=myopencastdomain</comment>
            
            This example will be check the conection with these Opencast and list all multimedia objects from PuMuKIT find by regex host.
            
            <info> ** Example ( <error>execute</error> ):</info>
            
            <comment>php app/console pumukit:opencast:multiple:host:import --user=myuser --password=mypassword --host=myopencastdomain --force</comment>

EOT
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $this->opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
        $this->logger = $this->getContainer()->get('logger');

        $this->user = $this->input->getOption('user');
        $this->password = $this->input->getOption('password');
        $this->host = $this->input->getOption('host');
        $this->force = (true === $this->input->getOption('user'));

        $this->clientService = new ClientService(
            $this->host,
            $this->user,
            $this->password,
            '/engage/ui/watch.html',
            '/admin/index.html#/recordings',
            '/dashboard/index.html',
            false,
            'delete-archive',
            false,
            false,
            null,
            $this->logger,
            null
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkInputs();

        if ($this->checkOpencastStatus()) {
            $multimediaObjects = $this->getMultimediaObjects();
            $this->importOpencastTracks($multimediaObjects);
        }
    }

    private function checkInputs()
    {
        if (!$this->user || !$this->password || !$this->host) {
            throw new \Exception('Please, set values for user, password and host');
        }
    }

    private function checkOpencastStatus()
    {
        if ($this->clientService->getAdminUrl()) {
            return true;
        }

        return false;
    }

    private function getMultimediaObjects()
    {
        $multimediaObjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy(
            array(
                'properties.opencasturl' => new \MongoRegex("/$this->host/i"),
            )
        );

        return $multimediaObjects;
    }

    private function importOpencastTracks($multimediaObjects)
    {
        foreach ($multimediaObjects as $multimediaObject) {
            $mediaPackage = $this->clientService->getMediapackage($multimediaObject->getProperty('opencast'));
            $media = $this->getMediaPackageField($mediaPackage, 'media');
            $tracks = $this->getMediaPackageField($media, 'track');
            if (isset($tracks[0])) {
                $limit = count($tracks);
                for ($i = 0; $i < $limit; ++$i) {
                    $this->opencastImportService->createTrackFromMediaPackage($mediaPackage, $multimediaObject, $i);
                }
            } else {
                $this->opencastImportService->createTrackFromMediaPackage($mediaPackage, $multimediaObject);
            }
        }
    }

    public function getMediaPackageField($mediaFields = array(), $field = '')
    {
        if ($mediaFields && $field) {
            if (isset($mediaFields[$field])) {
                return $mediaFields[$field];
            }
        }

        return null;
    }
}
