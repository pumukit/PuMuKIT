<?php

declare(strict_types=1);

namespace Pumukit\BasePlayerBundle\Command;

use Pumukit\BasePlayerBundle\Services\DeliveryChecker;
use Pumukit\BasePlayerBundle\Services\DeliveryCheckResult;
use Pumukit\BasePlayerBundle\Services\DeliveryProbe;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pumukit:check:delivery', description: 'Request a temporary file through the real web servers and fail if it is served without a valid token')]
final class CheckDeliveryCommand extends Command
{
    public function __construct(private readonly DeliveryChecker $checker)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('connect-to', null, InputOption::VALUE_REQUIRED, 'Send every request to this host[:port], keeping the host of the URL for TLS and the virtual hosts')
            ->addOption('insecure', null, InputOption::VALUE_NONE, 'Do not verify the TLS certificates')
            ->addOption('downloads-url', null, InputOption::VALUE_REQUIRED, 'Base URL of the download server (pumukit.downloads_url by default)')
            ->addOption('frontend-url', null, InputOption::VALUE_REQUIRED, 'Base URL of the frontend (PUMUKIT_SCHEME://PUMUKIT_FRONTEND_HOST by default)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $probe = new DeliveryProbe($input->getOption('connect-to'), (bool) $input->getOption('insecure'));

        try {
            $results = $this->checker->check($probe, $input->getOption('downloads-url'), $input->getOption('frontend-url'));
        } catch (\InvalidArgumentException|\RuntimeException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->table(
            ['Check', 'Result', 'Detail'],
            array_map(static fn (DeliveryCheckResult $result): array => [$result->name, $result->passed ? 'OK' : 'FAIL', $result->detail], $results)
        );

        $failed = count(array_filter($results, static fn (DeliveryCheckResult $result): bool => !$result->passed));
        if ($failed > 0) {
            $io->error(sprintf('%d of %d delivery checks failed.', $failed, count($results)));

            return Command::FAILURE;
        }

        $io->success(sprintf('The %d delivery checks passed.', count($results)));

        return Command::SUCCESS;
    }
}
