<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class NumericalIDCommand extends Command
{
    private $dm;
    private $step;
    private $force;
    private $output;

    public function __construct(DocumentManager $documentManager)
    {
        $this->dm = $documentManager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:update:numerical:id')
            ->setDescription('Generate and update numerical ID on series and Multimedia Object.')
            ->addOption('step', 'S', InputOption::VALUE_REQUIRED, 'Step of command. See help for more info', -99)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Set this parameter force the execution of this action')
            ->setHelp(
                <<<'EOT'

            Example:

            php app/console pumukit:update:numerical:id --step=generate
EOT
            )
        ;
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->step = $input->getOption('step');
        $this->force = (true === $input->getOption('force'));

        $this->output = $output;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        switch ($this->step) {
            case 'generate':
                $this->generateNewNumericalID();

                break;

            default:
                $output->writeln(' ***** Please select an valid step');
        }

        return 0;
    }

    private function generateNewNumericalID()
    {
        $this->output->writeln(
            ['<info> ***** Executing pumukit:update:numerical:id *****</info>'],
            ['Checking status...']
        );

        $status = $this->checkStatus();
        if (!$status) {
            return false;
        }

        $lastNumericalIDSeries = $this->getLastNumericalID(true);
        $lastNumericalIDMultimediaObject = $this->getLastNumericalID(false);

        $criteria = [
            'numerical_id' => ['$exists' => false],
        ];

        $multimediaObjects = $this->getMultimediaObjects($criteria, false);

        $this->output->writeln(
            [
                '',
                '<warning> ***** Updating multimedia objects *****</warning>',
            ]
        );

        $this->generateNumericalID($multimediaObjects, $lastNumericalIDMultimediaObject);

        $series = $this->getSeries($criteria, false);

        $this->output->writeln(
            [
                '',
                '<warning> ***** Updating series *****</warning>',
            ]
        );
        $this->generateNumericalID($series, $lastNumericalIDSeries);
    }

    private function checkStatus()
    {
        $criteria = [
            'numerical_id' => ['$exists' => false],
        ];

        $multimediaObjects = $this->getMultimediaObjects($criteria, true);
        $series = $this->getSeries($criteria, true);

        if ($multimediaObjects || $series) {
            $this->output->writeln(
                [
                    '<error>'.sprintf('There are %s multimedia objects and %s series with pumukit1id and not numerical ID, please execute query first', is_countable($multimediaObjects) ? count($multimediaObjects) : 0, is_countable($series) ? count($series) : 0).'</error>',
                ]
            );

            return false;
        }

        return true;
    }

    private function getMultimediaObjects($criteria, $withPumukit1Id = false)
    {
        $criteria = $this->createCriteria($criteria, $withPumukit1Id);

        return $this->dm->getRepository(MultimediaObject::class)->findBy($criteria);
    }

    private function getSeries($criteria, $withPumukit1Id = false)
    {
        $criteria = $this->createCriteria($criteria, $withPumukit1Id);

        return $this->dm->getRepository(Series::class)->findBy($criteria);
    }

    private function createCriteria($criteria, $withPumukit1Id)
    {
        $newCriteria = $criteria;
        $newCriteria['status'] = ['$ne' => MultimediaObject::STATUS_PROTOTYPE];
        $newCriteria['properties.pumukit1id'] = ['$exists' => $withPumukit1Id];

        return $newCriteria;
    }

    private function getLastNumericalID($series = false)
    {
        if ($series) {
            $series = $this->dm->getRepository(Series::class)->createQueryBuilder()
                ->field('numerical_id')->exists(true)
                ->sort(['numerical_id' => -1])
                ->getQuery()
                ->getSingleResult()
            ;

            if (!$series instanceof Series) {
                throw new \Exception('Series with numerical_id not found');
            }

            $lastNumericalID = $series->getNumericalID();
        } else {
            $multimediaObject = $this->dm->getRepository(MultimediaObject::class)->createQueryBuilder()
                ->field('numerical_id')->exists(true)
                ->sort(['numerical_id' => -1])
                ->getQuery()
                ->getSingleResult()
            ;

            if (!$multimediaObject instanceof MultimediaObject) {
                throw new \Exception('MultimediaObject with numerical_id not found');
            }
            $lastNumericalID = $multimediaObject->getNumericalID();
        }

        return $lastNumericalID;
    }

    private function generateNumericalID($elements, $lastNumericalID)
    {
        $progressBar = new ProgressBar($this->output, is_countable($elements) ? count($elements) : 0);
        $progressBar->setFormat('verbose');
        $progressBar->start();

        $i = 0;
        foreach ($elements as $element) {
            ++$i;
            $nextNumericalID = $lastNumericalID + 1;
            $element->setNumericalID($nextNumericalID);
            $lastNumericalID = $nextNumericalID;
            $progressBar->advance();

            if (0 == $i % 50) {
                $this->dm->flush();
            }
        }

        $this->dm->flush();
        $progressBar->finish();
    }
}
