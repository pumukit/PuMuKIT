<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class PasswordService
{
    protected $documentManager;
    protected $userPasswordEncoder;

    public function __construct(DocumentManager $documentManager, UserPasswordEncoderInterface $userPasswordEncoder)
    {
        $this->documentManager = $documentManager;
        $this->userPasswordEncoder = $userPasswordEncoder;
    }

    public function changePassword(UserInterface $user, string $password): void
    {
        try {
            $user->setPassword($this->userPasswordEncoder->encodePassword($user, $password));
            $this->documentManager->flush();
        } catch (\Exception $exception) {
            throw new \Exception($exception);
        }
    }
}
