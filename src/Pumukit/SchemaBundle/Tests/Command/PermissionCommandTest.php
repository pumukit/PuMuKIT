<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Command;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @internal
 *
 * @coversNothing
 */
class PermissionCommandTest extends PumukitTestCase
{
    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();
    }

    public function testAddPrintsAddedToProfile(): void
    {
        $this->seedProfile('Custom');

        $tester = $this->commandTester();
        $tester->execute([
            'profile' => 'Custom',
            'permission' => Permission::ACCESS_DASHBOARD,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('added to profile Custom', $tester->getDisplay());
    }

    public function testDeletePrintsRemovedFromProfile(): void
    {
        $profile = $this->seedProfile('Custom');
        $profile->addPermission(Permission::ACCESS_DASHBOARD);
        $this->dm->flush();

        $tester = $this->commandTester();
        $tester->execute([
            'profile' => 'Custom',
            'permission' => Permission::ACCESS_DASHBOARD,
            '--delete' => true,
        ]);

        $this->assertSame(Command::SUCCESS, $tester->getStatusCode());
        $this->assertStringContainsString('removed from profile Custom', $tester->getDisplay());
    }

    private function seedProfile(string $name): PermissionProfile
    {
        $profile = new PermissionProfile();
        $profile->setName($name);
        $this->dm->persist($profile);
        $this->dm->flush();

        return $profile;
    }

    private function commandTester(): CommandTester
    {
        $application = new Application(self::$kernel);
        $command = $application->find('pumukit:permission:update');

        return new CommandTester($command);
    }
}
