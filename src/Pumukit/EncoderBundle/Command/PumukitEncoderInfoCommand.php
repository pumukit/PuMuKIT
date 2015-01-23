<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\EncoderBundle\Document\Job;
      
class PumukitEncoderInfoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:info')
            ->setDescription('Pumukit show job info')
            ->addArgument('id', InputArgument::REQUIRED, 'Job identifier to execute')
            ->setHelp(<<<EOT
TODO


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

        if (($job = $dm->find('PumukitEncoderBundle:Job', $id)) === null) {
            throw new \RuntimeException("Not job found with id $id.");
        }

        $output->writeln("id: " . $job->getId());
        $output->writeln("status:" . Job::$statusTexts[$job->getStatus()]);                
        $output->writeln("mm: ". $job->getMmId());
        $output->writeln("profile: " . $job->getProfile());
        $output->writeln("cpu: " . $job->getCpu());
        $output->writeln("priority: " . $job->getPriority());                
        $output->writeln("timeini: " . $job->getTimeini('Y-m-d H:i:s'));
        $output->writeln("timestart: " . $job->getTimestart('Y-m-d H:i:s'));
        $output->writeln("timeend: " . $job->getTimeend('Y-m-d H:i:s'));
        $output->writeln("command: ");        


        $output->writeln($jobService->renderBat($job));
    }
}
