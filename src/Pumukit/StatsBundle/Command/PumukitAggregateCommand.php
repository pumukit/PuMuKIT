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
        $viewsService = $this->getContainer()->get('pumukit_stats.stats');
        $viewsService->aggregateViewsLog();
    }
}
