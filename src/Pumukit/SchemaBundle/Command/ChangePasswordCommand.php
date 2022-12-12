<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PasswordService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class ChangePasswordCommand extends Command
{
    protected static $defaultName = 'pumukit:change:password';

    private $documentManager;
    private $passwordService;

    public function __construct(DocumentManager $documentManager, PasswordService $passwordService)
    {
        $this->documentManager = $documentManager;
        $this->passwordService = $passwordService;

        parent::__construct();
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln(
            ['<info> ***** Executing pumukit:change:password *****</info>']
        );

        $username = $input->getArgument('username');
        $password = $input->getArgument('password');

        $user = $this->documentManager->getRepository(User::class)->findOneBy(['username' => $username]);
        if ($user instanceof UserInterface) {
            $this->passwordService->changePassword($user, $password);
            $output->writeln(['<info> User '.$username.' password changed </info>']);
        } else {
            $output->writeln(['<info> User '.$username.' not found </info>']);
        }

        return 0;
    }

    protected function configure(): void
    {
        $this
            ->setDescription('This command allows you to change password for one user')
            ->setHelp('This command allows you to change password for one user')
            ->addArgument(
                'username',
                InputArgument::REQUIRED,
                'user username'
            )
            ->addArgument(
                'password',
                InputArgument::REQUIRED,
                'user password'
            )
            ->setHelp(
                <<<'EOT'

            Example:

            php bin/console pumukit:change:password {username} {new_password}
EOT
            )
        ;
    }
}
