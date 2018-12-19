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
    private $id;
    private $force;
    private $clientService;

    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:import:multiple:host')
            ->setDescription('Import tracks from opencast passing data')
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Opencast user')
            ->addOption('password', 'p', InputOption::VALUE_REQUIRED, 'Opencast password')
            ->addOption('host', null, InputOption::VALUE_REQUIRED, 'Path to selected tracks from PMK using regex')
            ->addOption('id', null, InputOption::VALUE_OPTIONAL, 'ID of multimedia object to import')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<'EOT'
            
            Important:
            
            Before executing the command add Opencast URL MAPPING configuration with OC data you will access.
            
            ---------------
            
            Command to import all tracks from Opencast to PuMuKIT defining Opencast configuration
            
            <info> ** Example ( check and list ):</info>
            
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es"</comment>
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --id="5bcd806ebf435c25008b4581"</comment>
            
            This example will be check the connection with these Opencast and list all multimedia objects from PuMuKIT find by regex host.
            
            <info> ** Example ( <error>execute</error> ):</info>
            
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --force</comment>
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --id="5bcd806ebf435c25008b4581" --force</comment>

EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $this->opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
        $this->logger = $this->getContainer()->get('logger');

        $this->user = trim($this->input->getOption('user'));
        $this->password = trim($this->input->getOption('password'));
        $this->host = trim($this->input->getOption('host'));
        $this->id = $this->input->getOption('id');
        $this->force = (true === $this->input->getOption('force'));

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
            true,
            null,
            $this->logger,
            null
        );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->checkInputs();

        if ($this->checkOpencastStatus()) {
            $multimediaObjects = $this->getMultimediaObjects();
            if ($this->force) {
                $this->importOpencastTracks($multimediaObjects);
            } else {
                $this->showMultimediaObjects($multimediaObjects);
            }
        }
    }

    /**
     * @throws \Exception
     */
    private function checkInputs()
    {
        if (!$this->user || !$this->password || !$this->host) {
            throw new \Exception('Please, set values for user, password and host');
        }

        if ($this->id) {
            $validate = preg_match('/^[a-f\d]{24}$/i', $this->id);
            if (0 === $validate || false === $validate) {
                throw new \Exception('Please, use a valid ID');
            }
        }
    }

    /**
     * @return bool
     */
    private function checkOpencastStatus()
    {
        if ($this->clientService->getAdminUrl()) {
            return true;
        }

        return false;
    }

    /**
     * @return mixed
     */
    private function getMultimediaObjects()
    {
        $criteria = array(
            'properties.opencasturl' => new \MongoRegex("/$this->host/i"),
        );

        if ($this->id) {
            $criteria['_id'] = new \MongoId($this->id);
        }

        $multimediaObjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria);

        return $multimediaObjects;
    }

    /**
     * @param $multimediaObjects
     */
    private function importOpencastTracks($multimediaObjects)
    {
        $this->output->writeln(
            array(
                '',
                '<info> **** Adding tracks to multimedia object **** </info>',
                '',
                '<comment> ----- Total: </comment>'.count($multimediaObjects),
            )
        );

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

    /**
     * @param array  $mediaFields
     * @param string $field
     *
     * @return mixed|null
     */
    private function getMediaPackageField($mediaFields = array(), $field = '')
    {
        if ($mediaFields && $field) {
            if (isset($mediaFields[$field])) {
                return $mediaFields[$field];
            }
        }

        return null;
    }

    /**
     * @param $multimediaObjects
     */
    private function showMultimediaObjects($multimediaObjects)
    {
        $this->output->writeln(
            array(
                '',
                '<info> **** Finding Multimedia Objects **** </info>',
                '',
                '<comment> ----- Total: </comment>'.count($multimediaObjects),
            )
        );

        foreach ($multimediaObjects as $multimediaObject) {
            $this->output->writeln(' Multimedia Object: '.$multimediaObject->getId().' - URL: '.$multimediaObject->getProperty('opencasturl'));
        }
    }
}
