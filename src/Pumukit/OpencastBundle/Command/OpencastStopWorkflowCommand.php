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
            ->addOption('id', null, InputOption::VALUE_REQUIRED, 'Set this parameter to stop workflow with given id')
            ->setHelp(<<<EOT
Command to stop workflows in Opencast Server.

Given id, will stop that workflow, all finished otherwise.

EOT
                      );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $opencastWorkflowService = $this->getContainer()->get('pumukit_opencast.workflow');

        $id = $input->getOption('id');
        $result = $opencastWorkflowService->stopSucceededWorkflows($id);
        if (!$result) {
            $output->writeln('Error on stopping workflows');
            return -1;
        }

        $output->writeln('Successfully stopped workflows');

        return 1;
    }
}