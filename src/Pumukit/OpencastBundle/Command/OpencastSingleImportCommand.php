<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OpencastSingleImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:import')
            ->setDescription('Import a single opencast recording')
            ->addArgument('id', InputArgument::REQUIRED, 'Opencast id to import')
            ->addOption('invert', 'i', InputOption::VALUE_NONE, 'Inverted recording (CAMERA <-> SCREEN)')
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

        if ($mmobjRepo->findOneBy(array('properties.opencast' => $opencastId))) {
            $output->writeln('Mediapackage '.$opencastId.' has already been imported, skipping to next mediapackage');
        } else {
            $opencastImportService->importRecording($opencastId, $input->getOption('invert'));
        }
    }
}
