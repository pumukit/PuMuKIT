<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Command;

use Pumukit\StatsBundle\Services\ViewsDailyStatsService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pumukit:stats:rollup', description: 'Build the daily view statistics of the statistics panel from ViewsLog')]
final class PumukitStatsRollupCommand extends Command
{
    public function __construct(private readonly ViewsDailyStatsService $dailyStats)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('from', null, InputOption::VALUE_REQUIRED, 'First day to rebuild (YYYY-MM-DD). Default: the last day already built, or the first day of ViewsLog')
            ->addOption('to', null, InputOption::VALUE_REQUIRED, 'Last day to rebuild (YYYY-MM-DD). Default: yesterday')
            ->setHelp(
                <<<'EOT'
The statistics panel reads ViewsDailyStats, which every new view updates. Run this command once
after upgrading to build the days already stored in ViewsLog. It can be stopped and run again:
without options it continues from the last day built.

<info>php bin/console pumukit:stats:rollup</info>
<info>php bin/console pumukit:stats:rollup --from=2024-01-01 --to=2024-12-31</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $today = new \DateTimeImmutable('today');

        $from = $this->date($input->getOption('from')) ?? $this->dailyStats->lastStatsDay() ?? $this->dailyStats->firstLogDay();
        $to = $this->date($input->getOption('to')) ?? $today->modify('-1 day');
        if (null === $from) {
            $io->success('There are no views to build.');

            return Command::SUCCESS;
        }
        if ($to > $today) {
            $to = $today;
        }
        if ($from > $to) {
            $io->success('The daily view statistics are up to date.');

            return Command::SUCCESS;
        }

        $days = $from->diff($to)->days + 1;
        $io->text(sprintf('Building %d days, from %s to %s.', $days, $from->format('Y-m-d'), $to->format('Y-m-d')));
        $progress = $io->createProgressBar($days);
        $progress->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message% %elapsed:6s%/%estimated:-6s%');

        $views = 0;
        for ($day = $from; $day <= $to; $day = $day->modify('+1 day')) {
            $progress->setMessage($day->format('Y-m-d'));
            $views += $this->dailyStats->rebuildDay($day, $day == $today);
            $progress->advance();
        }
        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf('%d views built.', $views));

        return Command::SUCCESS;
    }

    private function date(?string $value): ?\DateTimeImmutable
    {
        if (null === $value) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new \InvalidArgumentException(sprintf('Invalid date "%s", expected YYYY-MM-DD.', $value));
        }

        return $date;
    }
}
