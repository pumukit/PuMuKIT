<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Command;

use Pumukit\StatsBundle\Services\StatsService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class PumukitAggregateCommand extends Command
{
    /** @var StatsService */
    private $statsService;

    public function __construct(StatsService $statsService)
    {
        $this->statsService = $statsService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:stats:aggregate')
            ->setDescription('Aggregate ViewsLog collections in ViewsAggregation')
            ->setHelp(
                <<<'EOT'
Aggregate ViewsLog collections in ViewsAggregation to improve the performance of generating stats.
Examples:
<info>php app/console pumukit:stats:aggregate</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->statsService->aggregateViewsLog();

        return 0;
    }
}
