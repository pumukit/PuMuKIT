<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Security;

use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\User\UserCheckerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class LoginAttemptUserChecker implements UserCheckerInterface
{
    public function __construct(private readonly DocumentManager $documentManager) {}

    public function checkPreAuth(UserInterface $user): void
    {
        if (!$user instanceof User) {
            return;
        }

        if (!$user->isEnabled() && $user->isResetLoginAttemptsAllowed()) {
            $user->resetLoginAttempts();
            $this->documentManager->flush();
        }

        if (!$user->canLogin() || !$user->isEnabled()) {
            throw new CustomUserMessageAccountStatusException('Invalid credentials or account temporarily blocked.');
        }
    }

    public function checkPostAuth(UserInterface $user, ?TokenInterface $token = null): void {}
}
