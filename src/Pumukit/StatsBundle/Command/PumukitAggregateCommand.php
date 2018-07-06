<?php

namespace Pumukit\StatsBundle\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;

class PumukitAggregateCommand extends ContainerAwareCommand
{
    private $dm = null;

    protected function configure()
    {
        $this
            ->setName('pumukit:stats:aggregate')
            ->setDescription('Aggregate ViewsLog collections in ViewsAggregation')
            ->setHelp(<<<'EOT'
Examples:
<info>php app/console pumukit:stats:aggregate</info>

EOT
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->dm = $this->getContainer()->get('doctrine_mongodb')->getManager();

        $viewsLogColl = $this->dm->getDocumentCollection('PumukitStatsBundle:ViewsLog');

        $pipeline = array(
            array(
                '$group' => array(
                    '_id' => array(
                        'mm' => '$multimediaObject',
                        'day' => array(
                            '$dateToString' => array(
                                'format' => '%Y-%m-%d',
                                'date' => '$date',
                            ),
                        ),
                    ),
                    'multimediaObject' => array('$first' => '$multimediaObject'),
                    'series' => array('$first' => '$series'),
                    'date' => array('$first' => '$date'),
                    'numView' => array('$sum' => 1),
                ),
            ),
            array(
                '$project' => array(
                    '_id' => 0,
                    'multimediaObject' => 1,
                    'series' => 1,
                    'date' => 1,
                    'numView' => 1,
                ),
            ),
            array('$out' => 'ViewsAggregation'),
        );

        $viewsLogColl->aggregate($pipeline);
    }
}
