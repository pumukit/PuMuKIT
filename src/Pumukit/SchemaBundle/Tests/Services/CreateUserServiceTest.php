<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Services\CreateUserService;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

/**
 * @internal
 *
 * @coversNothing
 */
class CreateUserServiceTest extends PumukitTestCase
{
    private $createUserService;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];

        static::bootKernel($options);
        parent::setUp();

        $dispatcher = new EventDispatcher();
        $userDispatcher = new UserEventDispatcherService($dispatcher);
        $permissionProfileDispatcher = new PermissionProfileEventDispatcherService($dispatcher);

        $permissionService = new PermissionService($this->dm);
        $permissionProfileService = new PermissionProfileService(
            $this->dm,
            $permissionProfileDispatcher,
            $permissionService
        );

        $userPasswordEncoder = $this->getMockBuilder(UserPasswordEncoder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $personService = $this->getMockBuilder(PersonService::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->createUserService = new CreateUserService(
            $this->dm,
            $userPasswordEncoder,
            $permissionProfileService,
            $personService,
            $userDispatcher
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $this->dm->close();

        $this->createUserService = null;
        gc_collect_cycles();
    }

    public function testShouldCreateUser(): void
    {
        $userValues = $this->generateRandomUserValues();
        $user = $this->createUserService->createUser(
            $userValues['username'],
            $userValues['password'],
            $userValues['email'],
            $userValues['fullName'],
            $userValues['permissionProfile']
        );

        static::assertNotEquals($user->getUsername(), $userValues['username']);
        static::assertEquals($user->getUsername(), strtolower($userValues['username']));
        static::assertNotEquals($user->getPassword(), $userValues['password']);
        static::assertNotEquals($user->getEmail(), $userValues['email']);
        static::assertEquals($user->getEmail(), strtolower($userValues['email']));
        static::assertFalse($user->isSuperAdmin());

        $associatedPermissionProfile = $userValues['permissionProfile'];
        $randomPermissionProfile = $this->generateRandomPermissionProfile();
        static::assertNotEquals($randomPermissionProfile->getId(), $associatedPermissionProfile->getId());
    }

    public function testShouldCreateSuperAdminUser(): void
    {
        $userValues = $this->generateRandomUserValues();
        $user = $this->createUserService->createSuperAdmin(
            $userValues['username'],
            $userValues['password'],
            $userValues['email']
        );

        static::assertNotEquals($user->getUsername(), $userValues['username']);
        static::assertEquals($user->getUsername(), strtolower($userValues['username']));
        static::assertNotEquals($user->getPassword(), $userValues['password']);
        static::assertNotEquals($user->getEmail(), $userValues['email']);
        static::assertEquals($user->getEmail(), strtolower($userValues['email']));
        static::assertEquals($user->getFullName(), $userValues['username']);
        static::assertTrue($user->isSuperAdmin());
    }

    public function testShouldFailWhenUserExists(): void
    {
        $this->expectExceptionMessage('Username already on database');

        $userValues = $this->generateRandomUserValues();
        $this->createUserService->createUser(
            $userValues['username'],
            $userValues['password'],
            $userValues['email'],
            $userValues['fullName'],
            $userValues['permissionProfile']
        );

        $this->createUserService->createUser(
            $userValues['username'],
            $userValues['password'],
            $userValues['email'],
            $userValues['fullName'],
            $userValues['permissionProfile']
        );
    }

    private function generateRandomUserValues(): array
    {
        return [
            'username' => $this->getUserNameOfUserTest(),
            'password' => $this->getPasswordOfUserTest(),
            'email' => $this->getEmailOfUserTest(),
            'fullName' => $this->getFullNameOfUserTest(),
            'permissionProfile' => $this->generateDefaultPermissionProfile(),
        ];
    }

    private function getUserNameOfUserTest(): string
    {
        return 'UserTest';
    }

    private function getPasswordOfUserTest(): string
    {
        return 'passwordExample';
    }

    private function getEmailOfUserTest(): string
    {
        return 'USER@examplemail.com';
    }

    private function getFullNameOfUserTest(): string
    {
        return 'User Test';
    }

    private function generateDefaultPermissionProfile(): PermissionProfile
    {
        return $this->createPermissionProfile('Default Permission Profile', true);
    }

    private function generateRandomPermissionProfile(): PermissionProfile
    {
        return $this->createPermissionProfile('Another Permission Profile');
    }

    private function createPermissionProfile(string $name, bool $default = false): PermissionProfile
    {
        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setDefault($default);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        return $permissionProfile;
    }
}
