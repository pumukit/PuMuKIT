<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\EncoderBundle\Document\Job;

class PumukitEncoderExecuteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:job')
            ->setDescription('Pumukit execute a encoder job')
            ->addArgument('id', InputArgument::REQUIRED, 'Job identifier to execute')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter to re-execute jobs')
            ->setHelp(<<<EOT
TODO

The --force parameter ...

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $jobService = $this->getContainer()->get('pumukitencoder.job');
        
        if (($id = $input->getArgument('id')) === null) {
            throw new \RuntimeException("Argument 'ID' is required in order to execute this command correctly.");
        }

        if (($job = $dm->find('PumukitEncoderBundle:Job', $id)) === null) {
            throw new \RuntimeException("Not job found with id $id.");
        }

        //TODO STATUS is executing when this command is executed. Must be waiting.
        /*
        if ((!$input->getOption('force')) && (JOB::STATUS_WAITING != $job->getStatus())) {
            throw new \RuntimeException("The job is not in the waiting state");
        }
        */

        //TODO Add log.
        $jobService->execute($job);
    }
}
