<?php

namespace Pumukit\EncoderBundle\Command;

use Pumukit\EncoderBundle\Document\Job;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitEncoderExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:job')
            ->setDescription('Pumukit execute a encoder job')
            ->addArgument('id', InputArgument::REQUIRED, 'Job identifier to execute')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to re-execute jobs')
            ->setHelp(
                <<<'EOT'
The --force parameter ...

EOT
          )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $jobService = $this->getContainer()->get('pumukitencoder.job');

        if (null === ($id = $input->getArgument('id'))) {
            throw new \RuntimeException("Argument 'ID' is required in order to execute this command correctly.");
        }

        if (null === ($job = $dm->find(Job::class, $id))) {
            throw new \RuntimeException("Not job found with id {$id}.");
        }

        $jobService->execute($job);
    }
}
