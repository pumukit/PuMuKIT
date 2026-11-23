<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class PasswordService
{
    protected $documentManager;
    protected $userPasswordHasher;

    public function __construct(DocumentManager $documentManager, UserPasswordHasherInterface $userPasswordHasher)
    {
        $this->documentManager = $documentManager;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function changePassword(User $user, string $password): void
    {
        try {
            $user->setPassword($this->userPasswordHasher->hashPassword($user, $password));
            $this->documentManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception->getMessage(), 0, $exception);
        }
    }
}
