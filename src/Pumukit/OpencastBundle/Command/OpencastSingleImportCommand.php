<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class OpencastSingleImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:import')
            ->setDescription('Import a single opencast recording')
            ->addArgument('id', InputArgument::REQUIRED, 'Opencast id to import')
            ->addOption('invert', 'i', InputOption::VALUE_NONE, 'Inverted recording (CAMERA <-> SCREEN)')
            ->addOption('mmobjid', 'o', InputOption::VALUE_OPTIONAL, 'Use an existing multimedia object. Not create a new one')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $opencastId = $input->getArgument('id');
        if ($input->getOption('verbose')) {
            $output->writeln('Importing opencast recording: '.$opencastId);
        }
        $opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
        $mmobjRepo = $this->getContainer()->get('doctrine_mongodb')->getRepository('PumukitSchemaBundle:MultimediaObject');

        if ($mmObjId = $input->getOption('mmobjid')) {
            if ($mmobj = $mmobjRepo->find($mmObjId)) {
                $this->completeMultimediaObject($mmobj, $opencastId, $input->getOption('invert'));
            } else {
                $output->writeln('No multimedia object with id '.$mmObjId);
            }
        } else {
            if ($mmobjRepo->findOneBy(array('properties.opencast' => $opencastId))) {
                $output->writeln('Mediapackage '.$opencastId.' has already been imported, skipping to next mediapackage');
            } else {
                $opencastImportService->importRecording($opencastId, $input->getOption('invert'));
            }
        }
    }

    protected function completeMultimediaObject(MultimediaObject $multimediaObject, $opencastId, $invert)
    {
        $opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
        $opencastClient = $this->getContainer()->get('pumukit_opencast.client');
        $mmsService = $this->getContainer()->get('pumukitschema.multimedia_object');

        $mediaPackage = $opencastClient->getMediaPackage($opencastId);

        $properties = $opencastImportService->getMediaPackageField($mediaPackage, 'id');
        if ($properties) {
            $multimediaObject->setProperty('opencast', $properties);
            $multimediaObject->setProperty('opencasturl', $opencastClient->getPlayerUrl().'?id='.$properties);
        }
        $multimediaObject->setProperty('opencastinvert', boolval($invert));

        $media = $opencastImportService->getMediaPackageField($mediaPackage, 'media');
        $tracks = $opencastImportService->getMediaPackageField($media, 'track');
        if (isset($tracks[0])) {
            // NOTE: Multiple tracks
            for ($i = 0; $i < count($tracks); ++$i) {
                $track = $opencastImportService->createTrackFromMediaPackage($mediaPackage, $multimediaObject, $i);
            }
        } else {
            // NOTE: Single track
            $track = $opencastImportService->createTrackFromMediaPackage($mediaPackage, $multimediaObject);
        }

        $mmsService->updateMultimediaObject($multimediaObject);
    }
}
