<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Permission\Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Shared\Domain\PermissionRegistryInterface;

final class DebugPermissionsCommand extends Command
{
    public function __construct(
        private readonly PermissionRegistryInterface $permissionRegistry,
        string $name = null
    ) {
        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $permissions = $this->permissionRegistry->getAll();

        if (empty($permissions)) {
            $io->warning('No permissions were registered. Check if the CompilerPass is running and services are tagged correctly.');
            return Command::FAILURE;
        }

        $io->title('PUMUKIT Permission Registry Debug');
        $io->text('Validating permission discovery via Compiler Pass...');

        $headers = ['Permission Key', 'Description'];

        ksort($permissions);

        foreach ($permissions as $groupKey => $groupPermissions) {
            $parts = explode('.', $groupKey, 2);
            $layer = count($parts) > 1 && in_array($parts[0], ['ui', 'web']) ? strtoupper($parts[0]) : 'FUNCTIONAL';
            $domain = count($parts) > 1 ? $parts[1] : $parts[0];

            $io->section(sprintf('Layer: %s | Entity/Domain: %s (%d permissions)', $layer, $domain, count($groupPermissions)));

            $rows = [];
            foreach ($groupPermissions as $key => $description) {
                $rows[] = [$key, $description];
            }

            $io->table($headers, $rows);
        }

        $io->success(sprintf('Permission discovery successful. Total unique permission groups found: %d', count($permissions)));

        return Command::SUCCESS;
    }
}
