<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Application\Create;

use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserService
{
    public function __construct(
        private UserRepositoryInterface $repository,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function __invoke(CreateUserRequest $request): CreateUserResponse
    {
        CreateUserValidator::validate($request);

        $user = new User();
        $user->setUsername($request->username);
        $user->setEmail($request->email);
        $user->setEnabled($request->enabled);
        $user->setOrigin($request->origin);

        if ($request->fullName) {
            $user->setFullName($request->fullName);
        }

        if ($request->password) {
            $hashedPassword = $this->passwordHasher->hashPassword($user, $request->password);
            $user->setPassword($hashedPassword);
        }

        $this->repository->save($user);

        return new CreateUserResponse($user);
    }
}
