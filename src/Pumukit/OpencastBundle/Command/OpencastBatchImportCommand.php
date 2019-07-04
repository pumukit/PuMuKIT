<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\MultimediaObject;

class OpencastBatchImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:batchimport')
            ->setDescription('Import the complete opencast repository')
            ->addOption('invert', 'i', InputOption::VALUE_OPTIONAL, 'Inverted recording (CAMERA <-> SCREEN)')
          ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $startTime = microtime(true);
        $opencastClientService = $this->getContainer()->get('pumukit_opencast.client');
        $mediaPackages = $opencastClientService->getMediaPackages('', 1, 0);

        $invert = $input->getOption('invert');
        if (('0' === $invert) || ('1' === $invert)) {
            $invert = ('1' === $invert);
        } else {
            $invert = $this->getContainer()->getParameter('pumukit_opencast.batchimport_inverted');
        }

        $totalMediaPackages = $mediaPackages[0];
        $batchSize = 200;
        $batchPlace = 0;

        $output->writeln('Number of mediapackages: '.$mediaPackages[0]);

        while ($batchPlace < $totalMediaPackages) {
            $output->writeln('Importing recordings '.$batchPlace.' to '.($batchPlace + $batchSize));
            $mediaPackages = $opencastClientService->getMediaPackages('', $batchSize, $batchPlace);

            $opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
            $repositoryMultimediaObjects = $this->getContainer()->get('doctrine_mongodb')->getRepository(MultimediaObject::class);

            foreach ($mediaPackages[1] as $mediaPackage) {
                $output->writeln('Importing mediapackage: '.$mediaPackage['id']);
                if ($repositoryMultimediaObjects->findOneBy(['properties.opencast' => $mediaPackage['id']])) {
                    $output->writeln('Mediapackage '.$mediaPackage['id'].' has already been imported, skipping to next mediapackage');
                } else {
                    $opencastImportService->importRecording($mediaPackage['id'], $invert);
                }
            }
            $batchPlace = $batchPlace + $batchSize;
        }
        $stopTime = microtime(true);
        $output->writeln('Finished importing '.$totalMediaPackages.' recordings in '.($stopTime - $startTime).' seconds');
    }
}
