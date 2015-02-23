<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Pumukit\EncoderBundle\Document\Job;
      
class PumukitEncoderInfoCommand extends BasePumukitEncoderCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:info')
            ->setDescription('Pumukit show job info')
            ->setDefinition(array(
                new InputArgument('id', InputArgument::REQUIRED, 'Job identifier to execute'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'To output description in other formats', 'txt'),
            ))
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


        //$description[] = sprintf('<comment>Scope</comment>            %s', $definition->getScope());
        $output->writeln("<comment>Id</comment>                " . $job->getId());
        $output->writeln("<comment>Status</comment>            " . $this->formatStatus($job->getStatus()));
        $output->writeln("<comment>Mm</comment>                " . $job->getMmId());
        $output->writeln("<comment>Profile</comment>           " . $job->getProfile());
        $output->writeln("<comment>Cpu</comment>               " . $job->getCpu());
        $output->writeln("<comment>Priority</comment>          " . $job->getPriority());
        $output->writeln("<comment>Duration</comment>          " . $job->getDuration());
        $output->writeln("<comment>New Duration</comment>      " . $job->getNewDuration());
        $output->writeln("<comment>Timeini</comment>           " . $job->getTimeini('Y-m-d H:i:s'));
        $output->writeln("<comment>Timestart</comment>         " . $job->getTimestart('Y-m-d H:i:s'));
        $output->writeln("<comment>Timeend</comment>           " . $job->getTimeend('Y-m-d H:i:s'));
        $output->writeln("<comment>Command</comment>");
        $output->writeln($jobService->renderBat($job));
        $output->writeln("<comment>Out</comment>");
        $output->writeln($job->getOutput());
    }

}
