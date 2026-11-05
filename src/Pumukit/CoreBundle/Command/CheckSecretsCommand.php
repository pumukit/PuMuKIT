<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Pumukit\CoreBundle\Services\SampleSecretsChecker;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pumukit:check:secrets', description: 'Fail if the installation keeps the sample secrets of the repository')]
final class CheckSecretsCommand extends Command
{
    public function __construct(private readonly SampleSecretsChecker $checker)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        foreach ($this->checker->warnings() as $warning) {
            $io->warning($warning);
        }

        $found = $this->checker->sampleValues();
        if ([] !== $found) {
            $io->error(sprintf('Sample values found in: %s.', implode(', ', $found)));

            return Command::FAILURE;
        }

        $io->success('No sample secrets found.');

        return Command::SUCCESS;
    }
}
