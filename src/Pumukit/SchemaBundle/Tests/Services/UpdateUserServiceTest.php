<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Security\Permission;
use Pumukit\SchemaBundle\Services\CreateUserService;
use Pumukit\SchemaBundle\Services\PermissionProfileEventDispatcherService;
use Pumukit\SchemaBundle\Services\PermissionProfileService;
use Pumukit\SchemaBundle\Services\PermissionService;
use Pumukit\SchemaBundle\Services\PersonService;
use Pumukit\SchemaBundle\Services\UpdateUserService;
use Pumukit\SchemaBundle\Services\UserEventDispatcherService;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoder;

/**
 * @internal
 * @coversNothing
 */
class UpdateUserServiceTest extends PumukitTestCase
{
    private $createUserService;
    private $userPasswordEncoder;
    private $updateUserService;

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

        $this->userPasswordEncoder = $this->getMockBuilder(UserPasswordEncoder::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $personService = $this->getMockBuilder(PersonService::class)
            ->disableOriginalConstructor()
            ->getMock()
        ;

        $this->createUserService = new CreateUserService(
            $this->dm,
            $this->userPasswordEncoder,
            $permissionProfileService,
            $personService,
            $userDispatcher
        );

        $this->updateUserService = new UpdateUserService(
            $this->dm,
            $permissionProfileService,
            $this->userPasswordEncoder,
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

    public function testShouldUpdateUser(): void
    {
        $userValues = $this->generateRandomUserValues();
        $user = $this->createUserService->createUser(
            $userValues['username'],
            $userValues['password'],
            $userValues['email'],
            $userValues['fullName'],
            $userValues['permissionProfile']
        );

        $userName = 'test';
        $password = 'newPassword';
        $email = 'another@email.com';
        $fullName = 'Full name test';

        $user->setUsername($userName);
        $user->setPlainPassword($password);
        $user->setEmail($email);
        $user->setFullname($fullName);

        $this->updateUserService->update($user);

        $savedUser = $this->dm->getRepository(User::class)->findOneBy(['username' => $userName]);

        static::assertEquals($savedUser->getUsername(), $userName);
        static::assertNull($savedUser->getPlainPassword());

        static::assertEquals($savedUser->getEmail(), $email);
        static::assertEquals($savedUser->getFullName(), $fullName);

        static::assertTrue($savedUser->hasRole(Permission::ACCESS_DASHBOARD));
        static::assertTrue($savedUser->hasRole(Permission::ACCESS_ROLES));
        static::assertFalse($savedUser->hasRole(Permission::ACCESS_LIVE_EVENTS));

        $user->setPermissionProfile($this->generateRandomPermissionProfile());

        $this->updateUserService->update($user);

        $savedUser = $this->dm->getRepository(User::class)->findOneBy(['username' => $userName]);

        static::assertFalse($savedUser->hasRole(Permission::ACCESS_DASHBOARD));
        static::assertFalse($savedUser->hasRole(Permission::ACCESS_ROLES));
        static::assertTrue($savedUser->hasRole(Permission::ACCESS_LIVE_EVENTS));
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
        $permissions = [Permission::ACCESS_LIVE_EVENTS];
        if ($default) {
            $permissions = [Permission::ACCESS_DASHBOARD, Permission::ACCESS_ROLES];
        }

        $permissionProfile = new PermissionProfile();

        $permissionProfile->setName($name);
        $permissionProfile->setDefault($default);
        $permissionProfile->setPermissions($permissions);
        $permissionProfile->setScope(PermissionProfile::SCOPE_PERSONAL);

        $this->dm->persist($permissionProfile);
        $this->dm->flush();

        return $permissionProfile;
    }
}
