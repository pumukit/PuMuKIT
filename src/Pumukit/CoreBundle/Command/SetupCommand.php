<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

#[AsCommand(name: 'pumukit:setup', description: 'Create what PuMuKIT and its enabled bundles need in the database; it only adds what is missing, so it can run on every deployment')]
final class SetupCommand extends Command
{
    public const STEP_TAG = 'pumukit.setup';

    public function __construct(
        private readonly DocumentManager $documentManager,
        #[AutowireIterator(self::STEP_TAG)]
        private readonly iterable $steps,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user', null, InputOption::VALUE_REQUIRED, 'Username of the super administrator to create if it does not exist')
            ->addOption('email', null, InputOption::VALUE_REQUIRED, 'Email of the super administrator')
            ->addOption('password', null, InputOption::VALUE_REQUIRED, 'Password of the super administrator')
            ->setHelp(
                <<<'EOT'
Updates the indexes, loads the tags, roles and permission profiles that are missing, creates the super administrator
when --user is given and it does not exist, and runs the setup command of every enabled bundle.

A bundle adds its setup command by tagging it with "pumukit.setup", for example with
#[AutoconfigureTag('pumukit.setup')]. The command must only create what is missing.
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = $input->getOption('user');
        if (null !== $user && (null === $input->getOption('email') || null === $input->getOption('password'))) {
            $io->error('--user needs --email and --password.');

            return Command::FAILURE;
        }

        $commands = [
            ['doctrine:mongodb:schema:update', []],
            ['pumukit:init:repo', ['repo' => 'all']],
        ];

        if (null !== $user) {
            if ($this->documentManager->getRepository(User::class)->findOneBy(['username' => $user])) {
                $io->section('pumukit:create:user');
                $io->writeln(sprintf('The user "%s" already exists.', $user));
            } else {
                $commands[] = ['pumukit:create:user', [
                    'username' => $user,
                    'email' => $input->getOption('email'),
                    'password' => $input->getOption('password'),
                ]];
            }
        }

        foreach ($this->steps as $step) {
            $commands[] = [$step->getName(), []];
        }

        foreach ($commands as [$name, $arguments]) {
            $io->section($name);
            $stepInput = new ArrayInput(['command' => $name] + $arguments);
            $stepInput->setInteractive(false);

            $status = $this->getApplication()->find($name)->run($stepInput, $output);
            if (Command::SUCCESS !== $status) {
                $io->error(sprintf('"%s" failed: the setup stopped.', $name));

                return $status;
            }
        }

        $io->success('PuMuKIT is set up.');

        return Command::SUCCESS;
    }
}
