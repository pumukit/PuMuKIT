<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\OpencastBundle\Services\OpencastImportService;

class OpencastSingleImportCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:import')
            ->setDescription('Import a single opencast recording')
            ->addArgument(
                'id',
                InputArgument::REQUIRED,
                'Opencast id to import'
            )
        ;
    }

    protected function execute( InputInterface $input, OutputInterface $output)
    {
        $opencastId = $input->getArgument('id');
        if ($input->getOption('verbose')) {
            $output->writeln("Importing opencast recording: " . $opencastId);
        }
        $opencastImportService = $this->getContainer()->get('pumukit_opencast.import');
        $opencastImportService->importRecording($opencastId);
    }
}
