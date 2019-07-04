<?php

namespace Pumukit\StatsBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitAggregateCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('pumukit:stats:aggregate')
            ->setDescription('Aggregate ViewsLog collections in ViewsAggregation')
            ->setHelp(
                <<<'EOT'
Aggregate ViewsLog collections in ViewsAggregation to improve the performance of generating stats.


db.ViewsLog.aggregate([
[{
        '$group': {
            '_id': {
                'mm': '$multimediaObject',
                'day': {
                    $dateToString: {
                        format: '%Y-%m-%d',
                        date: '$date'
                    }
                }
            },
            'multimediaObject': {
                $first: '$multimediaObject'
            },
            'series': {
                $first: '$series'
            },
            'date': {
                $first: '$date'
            },
            'numView': {
                '$sum': 1
            }
        }
    },
    {
        '$project': {
            '_id': 0,
            'multimediaObject': 1,
            'series': 1,
            'date': 1,
            'numView': 1,
        }
    },
    {
        $out: 'ViewsAggregation'
    }
])


Examples:
<info>php app/console pumukit:stats:aggregate</info>

EOT
          )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $viewsService = $this->getContainer()->get('pumukit_stats.stats');
        $viewsService->aggregateViewsLog();
    }
}
