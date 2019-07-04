<?php

namespace Pumukit\OpencastBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\OpencastBundle\Services\ClientService;
use Pumukit\OpencastBundle\Services\OpencastImportService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

/**
 * Class MultipleOpencastHostImportCommand.
 */
class MultipleOpencastHostImportCommand extends ContainerAwareCommand
{
    private $opencastImportService;
    private $user;
    private $password;
    private $host;
    private $id;
    private $force;
    private $master;
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
            ->addOption('master', null, InputOption::VALUE_NONE, 'Import master tracks')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to execute this action')
            ->setHelp(<<<'EOT'
            
            Important:
            
            Before executing the command add Opencast URL MAPPING configuration with OC data you will access.
            
            ---------------
            
            Command to import all tracks from Opencast to PuMuKIT defining Opencast configuration
            
            <info> ** Example ( check and list ):</info>
            
            * Tracks without master
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es"</comment>
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --id="5bcd806ebf435c25008b4581"</comment>
            
            * Tracks master
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --master</comment>
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --master --id="5bcd806ebf435c25008b4581"</comment>
            
            This example will be check the connection with these Opencast and list all multimedia objects from PuMuKIT find by regex host.
            
            <info> ** Example ( <error>execute</error> ):</info>
            
            * Import tracks no master
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --force</comment>
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --id="5bcd806ebf435c25008b4581" --force</comment>
            
            * Import tracks master
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --master --force</comment>
            <comment>php app/console pumukit:opencast:import:multiple:host --user="myuser" --password="mypassword" --host="https://opencast-local.teltek.es" --master --id="5bcd806ebf435c25008b4581" --force</comment>

EOT
            );
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->opencastImportService = $this->getContainer()->get('pumukit_opencast.import');

        $this->user = trim($input->getOption('user'));
        $this->password = trim($input->getOption('password'));
        $this->host = trim($input->getOption('host'));
        $this->id = $input->getOption('id');
        $this->force = (true === $input->getOption('force'));
        $this->master = (true === $input->getOption('master'));

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
            $this->getContainer()->get('logger'),
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

        if ($this->checkOpencastStatus($this->clientService)) {
            $dm = $this->getContainer()->get('doctrine_mongodb.odm.document_manager');
            $multimediaObjects = $this->getMultimediaObjects($dm);
            if ($this->force) {
                if ($this->master) {
                    $this->importMasterTracks($output, $this->clientService, $this->opencastImportService, $multimediaObjects);
                } else {
                    $this->importBroadcastTracks($output, $this->clientService, $this->opencastImportService, $multimediaObjects);
                }
            } else {
                $this->showMultimediaObjects($output, $this->opencastImportService, $this->clientService, $multimediaObjects, $this->master);
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
     * @param ClientService $clientService
     *
     * @return bool
     *
     * @throws \Exception
     */
    private function checkOpencastStatus(ClientService $clientService)
    {
        if ($clientService->getAdminUrl()) {
            return true;
        }

        return false;
    }

    /**
     * @param DocumentManager $dm
     *
     * @return array|\Pumukit\SchemaBundle\Document\MultimediaObject[]
     */
    private function getMultimediaObjects(DocumentManager $dm)
    {
        $criteria = [
            'properties.opencasturl' => new \MongoRegex("/$this->host/i"),
        ];

        if ($this->id) {
            $criteria['_id'] = new \MongoId($this->id);
        }

        $multimediaObjects = $dm->getRepository(MultimediaObject::class)->findBy($criteria);

        return $multimediaObjects;
    }

    /**
     * @param OutputInterface       $output
     * @param ClientService         $clientService
     * @param OpencastImportService $opencastImportService
     * @param                       $multimediaObjects
     *
     * @throws \Exception
     */
    private function importBroadcastTracks(OutputInterface $output, ClientService $clientService, OpencastImportService $opencastImportService, $multimediaObjects)
    {
        $output->writeln(
            [
                '',
                '<info> **** Adding tracks to multimedia object **** </info>',
                '',
                '<comment> ----- Total: </comment>'.count($multimediaObjects),
            ]
        );

        foreach ($multimediaObjects as $multimediaObject) {
            if (!$multimediaObject->getTrackWithTag('presentation/delivery') && !$multimediaObject->getTrackWithTag('presenter/delivery')) {
                $this->importTrackOnMultimediaObject(
                    $output,
                    $clientService,
                    $opencastImportService,
                    $multimediaObject,
                    false
                );
            } else {
                $output->writeln('<info> Multimedia Object - '.$multimediaObject->getId().' have opencast tracks from OC imported');
            }
        }
    }

    /**
     * @param OutputInterface       $output
     * @param ClientService         $clientService
     * @param OpencastImportService $opencastImportService
     * @param                       $multimediaObjects
     *
     * @throws \Exception
     */
    private function importMasterTracks(OutputInterface $output, ClientService $clientService, OpencastImportService $opencastImportService, $multimediaObjects)
    {
        $output->writeln(
            [
                '',
                '<info> **** Import master tracks to multimedia object **** </info>',
                '',
                '<comment> ----- Total: </comment>'.count($multimediaObjects),
            ]
        );

        foreach ($multimediaObjects as $multimediaObject) {
            if (!$multimediaObject->getTrackWithTag('master')) {
                $this->importTrackOnMultimediaObject(
                    $output,
                    $clientService,
                    $opencastImportService,
                    $multimediaObject,
                    true
                );
            } else {
                $output->writeln('<info> Multimedia Object - '.$multimediaObject->getId().' have master tracks from OC imported');
            }
        }
    }

    /**
     * @param OutputInterface       $output
     * @param ClientService         $clientService
     * @param OpencastImportService $opencastImportService
     * @param MultimediaObject      $multimediaObject
     * @param                       $master
     *
     * @throws \Exception
     */
    private function importTrackOnMultimediaObject(OutputInterface $output, ClientService $clientService, OpencastImportService $opencastImportService, MultimediaObject $multimediaObject, $master)
    {
        if ($master) {
            $mediaPackage = $clientService->getMasterMediaPackage($multimediaObject->getProperty('opencast'));
            $trackTags = ['master'];
        } else {
            $mediaPackage = $clientService->getMediaPackage($multimediaObject->getProperty('opencast'));
            $trackTags = ['display'];
        }

        try {
            $opencastImportService->importTracksFromMediaPackage($mediaPackage, $multimediaObject, $trackTags);
            $this->showMessage($output, $opencastImportService, $multimediaObject, $mediaPackage);
        } catch (\Exception $exception) {
            $output->writeln('<error>Error - MMobj: '.$multimediaObject->getId().' and mediaPackage: '.$multimediaObject->getProperty('opencast').' with this error: '.$exception->getMessage().'</error>');
        }
    }

    /**
     * @param OutputInterface       $output
     * @param OpencastImportService $opencastImportService
     * @param ClientService         $clientService
     * @param                       $multimediaObjects
     * @param                       $master
     *
     * @throws \Exception
     */
    private function showMultimediaObjects(OutputInterface $output, OpencastImportService $opencastImportService, ClientService $clientService, $multimediaObjects, $master)
    {
        $message = '<info> **** Finding Multimedia Objects **** </info>';
        if ($master) {
            $message = '<info> **** Finding Multimedia Objects (master)**** </info>';
        }
        $output->writeln(
            [
                '',
                $message,
                '',
                '<comment> ----- Total: </comment>'.count($multimediaObjects),
            ]
        );

        foreach ($multimediaObjects as $multimediaObject) {
            if ($master) {
                $mediaPackage = $clientService->getMasterMediaPackage($multimediaObject->getProperty('opencast'));
                $this->showMessage($output, $opencastImportService, $multimediaObject, $mediaPackage);
            } else {
                $mediaPackage = $clientService->getMediaPackage($multimediaObject->getProperty('opencast'));
                $this->showMessage($output, $opencastImportService, $multimediaObject, $mediaPackage);
            }
        }
    }

    /**
     * @param OutputInterface       $output
     * @param OpencastImportService $opencastImportService
     * @param MultimediaObject      $multimediaObject
     * @param                       $mediaPackage
     */
    private function showMessage(OutputInterface $output, OpencastImportService $opencastImportService, MultimediaObject $multimediaObject, $mediaPackage)
    {
        $media = $opencastImportService->getMediaPackageField($mediaPackage, 'media');
        $tracks = $opencastImportService->getMediaPackageField($media, 'track');
        $tracksCount = 1;
        if (isset($tracks[0])) {
            $tracksCount = count($tracks);
        }

        $output->writeln(' Multimedia Object: '.$multimediaObject->getId().' - URL: '.$multimediaObject->getProperty('opencasturl').' - Tracks: '.$tracksCount);
    }
}
