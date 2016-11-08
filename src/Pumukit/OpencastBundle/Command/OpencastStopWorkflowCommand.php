<?php

namespace Pumukit\OpencastBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class OpencastStopWorkflowCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:opencast:workflow:stop')
            ->setDescription('Stop given workflow or all finished workflows')
            ->addOption('mediaPackageId', null, InputOption::VALUE_REQUIRED, 'Set this parameter to stop workflow with given mediaPackageId')
            ->setHelp(<<<'EOT'
Command to stop workflows in Opencast Server.

Given mediaPackageId, will stop that workflow, all finished otherwise.

EOT
                      );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = $this->getContainer()->get('logger');
        $opencastWorkflowService = $this->getContainer()->get('pumukit_opencast.workflow');
        $deleteArchiveMediaPackage = $this->getContainer()->getParameter('pumukit_opencast.delete_archive_mediapackage');

        if ($deleteArchiveMediaPackage) {
            $mediaPackageId = $input->getOption('mediaPackageId');
            $result = $opencastWorkflowService->stopSucceededWorkflows($mediaPackageId);
            if (!$result) {
                $output->writeln('<error>Error on stopping workflows</error>');
                $logger->error('['.__CLASS__.']('.__FUNCTION__.') Error on stopping workflows');

                return -1;
            }
            $output->writeln('<info>Successfully stopped workflows</info>');
            $logger->info('['.__CLASS__.']('.__FUNCTION__.') Successfully stopped workflows');
        } else {
            $output->writeln('<info>Not allowed to stop workflows</info>');
            $logger->warning('['.__CLASS__.']('.__FUNCTION__.') Not allowed to stop workflows');
        }

        return 1;
    }
}
