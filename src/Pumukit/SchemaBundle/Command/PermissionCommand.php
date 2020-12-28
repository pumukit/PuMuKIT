<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Command;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PermissionCommand extends Command
{
    private $permissionProfileService;
    private $permissionService;
    private $dm;

    public function __construct(PermissionProfileService $permissionProfileService, PermissionService $permissionService, DocumentManager $documentManager)
    {
        $this->permissionProfileService = $permissionProfileService;
        $this->permissionService = $permissionService;
        $this->dm = $documentManager;

        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('pumukit:permission:update')
            ->setDescription('Update the permissions of a profile')
            ->setDefinition([
                new InputArgument('profile', InputArgument::REQUIRED, 'The permission profile'),
                new InputArgument('permission', InputArgument::REQUIRED, 'The permission'),
                new InputOption('delete', null, InputOption::VALUE_NONE, 'User to delete a permission of a profile, add by default'),
            ])
            ->setHelp(
                <<<'EOT'
The <info>pumukit:permission:update</info> command adds/deletes a permission from a permission profile.

  <info>php app/console pumukit:permission:update admin ROLE_CUSTOM</info>
  <info>php app/console pumukit:permission:update --delete auto-ingest ROLE_CUSTOM</info>
EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileName = $input->getArgument('profile');
        $profile = $this->getProfile($profileName);
        $permissionName = $input->getArgument('permission');
        $this->checkPermission($permissionName);
        $delete = (true === $input->getOption('delete'));

        if ($delete) {
            $this->permissionProfileService->removePermission($profile, $permissionName);
            $output->writeln(sprintf('Profile "%s" has been deleted into profile %s.', $permissionName, $profileName));
        } else {
            $this->permissionProfileService->addPermission($profile, $permissionName);
            $output->writeln(sprintf('Profile "%s" has been added into profile %s.', $permissionName, $profileName));
        }

        return 0;
    }

    private function getProfile(string $profileName)
    {
        $profile = $this->dm->getRepository(PermissionProfile::class)->findOneBy(['name' => $profileName]);

        if (!$profile) {
            throw new \InvalidArgumentException(sprintf('No permission profile with name %s', $profileName));
        }

        return $profile;
    }

    private function checkPermission($permissionName): void
    {
        if (!$this->permissionService->exists($permissionName)) {
            throw new \InvalidArgumentException(sprintf('No permission with name %s', $permissionName));
        }
    }
}
