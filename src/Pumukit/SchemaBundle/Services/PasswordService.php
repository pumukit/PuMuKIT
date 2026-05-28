<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordService
{
    protected $documentManager;
    protected $userPasswordHasher;

    public function __construct(DocumentManager $documentManager, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->documentManager = $documentManager;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function changePassword(UserInterface $user, string $password): void
    {
        try {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
            $this->documentManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception);
        }
    }
}
