<?php

namespace Pumukit\SchemaBundle\Command;

use Pumukit\SchemaBundle\Document\PermissionProfile;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PermissionCommand extends ContainerAwareCommand
{
    /**
     * @see Command
     */
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

    /**
     * @see Command
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $profileName = $input->getArgument('profile');
        $profile = $this->getProfile($profileName);
        $permissionName = $input->getArgument('permission');
        $this->checkPermission($permissionName);
        $delete = (true === $input->getOption('delete'));

        $permissionProfileService = $this->getContainer()->get('pumukitschema.permissionprofile');

        if ($delete) {
            $permissionProfileService->removePermission($profile, $permissionName);
            $output->writeln(sprintf('Profile "%s" has been deleted into profile %s.', $permissionName, $profileName));
        } else {
            $permissionProfileService->addPermission($profile, $permissionName);
            $output->writeln(sprintf('Profile "%s" has been added into profile %s.', $permissionName, $profileName));
        }
    }

    private function getProfile($profileName)
    {
        $dm = $this->getContainer()->get('doctrine_mongodb')->getManager();
        $repo = $dm->getRepository(PermissionProfile::class);
        $profile = $repo->findOneByName($profileName);

        if (!$profile) {
            throw new \InvalidArgumentException(sprintf('No permission profile with name %s', $profileName));
        }

        return $profile;
    }

    private function checkPermission($permissionName)
    {
        $permissionService = $this->getContainer()->get('pumukitschema.permission');

        if (!$permissionService->exists($permissionName)) {
            throw new \InvalidArgumentException(sprintf('No permission with name %s', $permissionName));
        }
    }
}
