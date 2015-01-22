<?php

namespace Pumukit\EncoderBundle\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class PumukitEncoderListCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:list')
            ->setDescription('Pumukit list stats about encoder jobs')
            ->setHelp(<<<EOT
TODO

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->listCpus($output);
        $this->listJobs($output);        
    }


    private function listCpus(OutputInterface $output)
    {

        $cpuService = $this->getContainer()->get('pumukitencoder.cpu');
        $cpus = $cpuService->getCpus();
        
        $output->writeln("<info>CPUS:</info>");
        $table = new Table($output);
        $table->setHeaders(array('Name', 'Type', 'Host', 'Number', 'Description'));

        foreach($cpus as $name => $cpu) {
            $table->addRow(array(
                $name,
                $cpu['type'],
                $cpu['host'],
                $cpu['number'] .'/'. $cpu['max'],
                $cpu['description']
            ));
        }
        $table->render();
    }

    private function listJobs(OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $jobRepo = $dm->getRepository('PumukitEncoderBundle:Job');
        $jobService = $this->getContainer()->get('pumukitencoder.job');

        $stats = $jobService->getAllJobsStatus();
        
        $output->writeln("<info>JOBS NUMBERS:</info>");
        $table = new Table($output);
        $table->setHeaders(array_keys($stats));
        $table->addRow(array_values($stats));
        $table->render();
    }
}
