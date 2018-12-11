<?php

namespace Pumukit\SchemaBundle\Command;

use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class NumericalIDCommand extends ContainerAwareCommand
{
    private $dm;
    private $step;
    private $force;
    private $output;

    protected function configure()
    {
        $this
            ->setName('pumukit:update:numerical:id')
            ->setDescription('Generate and update numerical ID on series and Multimedia Object.')
            ->addOption('step', 'S', InputOption::VALUE_REQUIRED, 'Step of command. See help for more info', -99)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter force the execution of this action')
            ->setHelp(<<<EOT
            
EOT
            );
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $this->step = $input->getOption('step');
        $this->force = (true === $input->getOption('force'));

        $this->output = $output;
    }

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        switch ($this->step) {
            case 'generate':
                $this->generateNewNumericalID();
                break;
            case 'update':
                $this->updateNumericalID();
                break;
            default:
                $output->writeln(' ***** Please select an valid step');
        }
    }

    private function generateNewNumericalID()
    {
        $this->output->writeln(
            array('<info> ***** Generate numerical ID *****</info>'),
            array('Checking status...')
        );

        $status = $this->checkStatus();

        if (!$status) {
            return false;
        }

        // TODO: Generate numerical ID

        return true;
    }

    private function updateNumericalID()
    {
        //$this->updateSeriesNumericalID();

        $this->updateMultimediaObjectNumericalID();
    }

    private function updateSeriesNumericalID()
    {
        $this->output->writeln(
            array('<info> ***** Updating series numerical ID ***** </info>')
        );

        $series = $this->getSeries(array(), true);

        $progressBar = new ProgressBar($this->output, count($series));
        $progressBar->start();

        $i = 0;
        foreach ($series as $oneSeries) {
            $i++;
            $oneSeries->setNumericalID($oneSeries->getProperty('pumukit1id'));
            $progressBar->advance();
            if (0 == $i % 100) {
                $this->dm->flush();
                $this->dm->clear();
            }
        }

        $this->dm->flush();
        $progressBar->finish();
    }

    private function updateMultimediaObjectNumericalID()
    {
        $this->output->writeln(
            array(
                '',
                '<info> ***** Updating multimedia objects numerical ID *****</info>'
            )
        );

        $multimediaObjects = $this->getMultimediaObjects(array(), true, 100);

        $progressBar = new ProgressBar($this->output, count($multimediaObjects));
        $progressBar->start();

        $i = 0;
        foreach ($multimediaObjects as $oneSeries) {
            $i++;
            $oneSeries->setNumericalID($oneSeries->getProperty('pumukit1id'));
            $progressBar->advance();
            $this->dm->flush();
        }

        $progressBar->finish();
    }

    private function checkStatus()
    {
        $criteria = array(
            'numericalID' => array('$exists' => false),
        );

        $multimediaObjects = $this->getMultimediaObjects($criteria, true);
        $series = $this->getSeries($criteria, true);

        if ($multimediaObjects || $series) {
            $this->output->writeln(
                array(
                    '<error>'.sprintf('There are %s multimedia objects and %s series with pumukit1id and not numerical ID, please execute step update first', count($multimediaObjects), count($series)).'</error>',
                )
            );

            return false;
        }

        return true;
    }

    private function getMultimediaObjects($criteria, $withPumukit1Id = false, $limit = 0)
    {
        $criteria = $this->createCriteria($criteria, $withPumukit1Id);

        $multimediaObjects = $this->dm->getRepository('PumukitSchemaBundle:MultimediaObject')->findBy($criteria, $limit);

        return $multimediaObjects;
    }

    private function getSeries($criteria, $withPumukit1Id = false)
    {
        $criteria = $this->createCriteria($criteria, $withPumukit1Id);

        $series = $this->dm->getRepository('PumukitSchemaBundle:Series')->findBy($criteria);

        return $series;
    }

    private function createCriteria($criteria, $withPumukit1Id)
    {
        $newCriteria = $criteria;
        $newCriteria['properties.pumukit1id'] = array('$exists' => $withPumukit1Id);

        return $newCriteria;
    }
}
