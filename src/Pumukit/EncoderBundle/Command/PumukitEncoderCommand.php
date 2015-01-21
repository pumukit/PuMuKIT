<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Pumukit\SchemaBundle\Document\Tag;

class PumukitInitTagsCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:encoder:job')
            ->setDescription('Pumukit execute a encoder job')
            ->addArgument('id', InputArgument::InputArgument::REQUIRED, 'Job identifier to execute')
            ->setHelp(<<<EOT
TODO

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $jobSerice = $this->getContainer()->get('pumukitencoder.job');
        
        if (($id = $input->getArgument('id')) === null) {
            throw new \RuntimeException("Argument 'ID' is required in order to execute this command correctly.");
        }

        if (($job = $dm->find('PumukitEncoderBundle:Job', $id)) === null) {
            throw new \RuntimeException("Not job found with id $id.");
        }

        //TODO Add log.
        $jobService->execute($job);
    }
}
