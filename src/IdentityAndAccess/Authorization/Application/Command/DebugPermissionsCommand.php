<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Application\Command;

use App\Shared\Domain\PermissionRegistryInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

        // Group permissions by context
        $groupedPermissions = [];
        foreach ($permissions as $permission) {
            $groupedPermissions[$permission->context][] = $permission;
        }

        ksort($groupedPermissions);

        $headers = ['Permission Key', 'Description', 'Type'];

        foreach ($groupedPermissions as $context => $contextPermissions) {
            $io->section(sprintf('Context: %s (%d permissions)', $context, count($contextPermissions)));

            $rows = [];
            foreach ($contextPermissions as $permission) {
                $rows[] = [
                    $permission->id,
                    $permission->description,
                    $permission->type->value,
                ];
            }

            $io->table($headers, $rows);
        }

        $totalPermissions = count($permissions);
        $io->success(sprintf('Permission discovery successful. Total permissions found: %d in %d contexts', $totalPermissions, count($groupedPermissions)));

        return Command::SUCCESS;
    }
}
