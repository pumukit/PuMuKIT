<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\PermissionProfile;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class CreateUserService extends CommonUserService
{
    protected $userRepository;
    private $userPasswordEncoder;
    private $personService;
    private $dispatcher;

    public function __construct(
        DocumentManager $objectManager,
        UserPasswordEncoderInterface $userPasswordEncoder,
        PermissionProfileService $permissionProfileService,
        PersonService $personService,
        UserEventDispatcherService $dispatcher
    ) {
        parent::__construct($objectManager, $permissionProfileService);
        $this->userPasswordEncoder = $userPasswordEncoder;
        $this->personService = $personService;
        $this->dispatcher = $dispatcher;

        $this->userRepository = $objectManager->getRepository(User::class);
    }

    public function createUser(string $username, string $password, string $email, string $fullName, PermissionProfile $permissionProfile): UserInterface
    {
        return $this->create($username, $password, $email, $fullName, $permissionProfile);
    }

    public function createSuperAdmin(string $username, string $password, string $email): UserInterface
    {
        return $this->create($username, $password, $email, null, null);
    }

    private function create(string $username, string $password, string $email, ?string $fullName, ?PermissionProfile $permissionProfile): UserInterface
    {
        if ($this->userExists(['username' => strtolower($username)])) {
            throw new \Exception('Username already on database');
        }

        if ($this->userExists(['email' => $email])) {
            throw new \Exception('Email already on database');
        }

        $user = new User();
        $user->setUsername($username);
        $user->setFullName($fullName ?? $username);
        $user->setEmail($email);
        $user->setPassword($this->userPasswordEncoder->encodePassword($user, $password));
        $user->setEnabled(true);
        if ($permissionProfile instanceof PermissionProfile) {
            $user->setPermissionProfile($permissionProfile);
        }

        $this->updateUserRolesAndPermission($user);
        $this->userRepository->save($user);

        $this->personService->referencePersonIntoUser($user);

        $this->dispatcher->dispatchCreate($user);

        return $user;
    }
}
