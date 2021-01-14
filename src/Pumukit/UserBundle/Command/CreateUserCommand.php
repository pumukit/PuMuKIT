<?php

declare(strict_types=1);

namespace Pumukit\UserBundle\Command;

use Pumukit\UserBundle\Services\CreateUserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateUserCommand extends Command
{
    protected static $defaultName = 'pumukit:create:user';

    private $createUserService;

    public function __construct(CreateUserService $createUserService)
    {
        $this->createUserService = $createUserService;

        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            ['<info> ***** Executing pumukit:create:user *****</info>']
        );

        $username = $input->getArgument('username');
        $email = $input->getArgument('email');
        $password = $input->getArgument('password');

        $userWasCreated = $this->createUserService->createSuperAdmin($username, $password, $email);

        if ($userWasCreated) {
            $message = '<info> User '.$username.' created </info>';
        } else {
            $message = '<error> User '.$username.' already on DB </error>';
        }

        $output->writeln(
            [$message]
        );

        return 0;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('This command allows you to create a SUPER ADMIN user')
            ->setHelp('This command allows you to create a SUPER ADMIN  user')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'admin\'s username'
            )
            ->addArgument(
                'email',
                InputArgument::REQUIRED,
                'admin\'s email'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'admin\'s password'
            )
        ;
    }
}
