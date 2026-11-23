<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\Security\Core\Exception\AccountStatusException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginAttemptListener implements EventSubscriberInterface
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly PasswordHasherFactoryInterface $passwordHasherFactory,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginFailureEvent::class => 'onLoginFailure',
            LoginSuccessEvent::class => 'onLoginSuccess',
        ];
    }

    public function onLoginFailure(LoginFailureEvent $event): void
    {
        $passport = $event->getPassport();
        if (!$this->checksAPassword($passport)) {
            return;
        }

        if ($this->passwordWasNotChecked($event->getException())) {
            $this->hashThePassword($passport);

            return;
        }

        if (!$event->getException() instanceof BadCredentialsException) {
            return;
        }

        $window = (new \DateTime())->sub(\DateInterval::createFromDateString(User::RESET_LOGIN_ATTEMPTS_INTERVAL));
        $attempts = ['$min' => [['$add' => [['$ifNull' => ['$loginAttempt', 0]], 1]], User::MAX_LOGIN_ATTEMPTS]];

        $this->documentManager->getDocumentCollection(User::class)->updateOne(
            ['username' => $passport->getBadge(UserBadge::class)->getUserIdentifier()],
            [
                ['$set' => [
                    'loginAttempt' => ['$cond' => [['$lt' => ['$lastLoginAttempt', new UTCDateTime($window)]], 1, $attempts]],
                    'lastLoginAttempt' => new UTCDateTime(),
                ]],
                ['$set' => [
                    'enabled' => ['$cond' => [['$gte' => ['$loginAttempt', User::MAX_LOGIN_ATTEMPTS]], false, ['$ifNull' => ['$enabled', false]]]],
                ]],
            ]
        );
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();
        if (!$user instanceof User || !$this->checksAPassword($event->getPassport()) || (0 === $user->getLoginAttempt() && $user->isEnabled())) {
            return;
        }

        $this->documentManager->getDocumentCollection(User::class)->updateOne(
            ['_id' => new ObjectId((string) $user->getId())],
            ['$set' => ['loginAttempt' => 0, 'enabled' => true, 'lastLoginAttempt' => new UTCDateTime()]]
        );
    }

    private function passwordWasNotChecked(\Throwable $exception): bool
    {
        for ($current = $exception; null !== $current; $current = $current->getPrevious()) {
            if ($current instanceof UserNotFoundException || $current instanceof AccountStatusException) {
                return true;
            }
        }

        return false;
    }

    private function hashThePassword(Passport $passport): void
    {
        try {
            $this->passwordHasherFactory->getPasswordHasher(User::class)->hash($passport->getBadge(PasswordCredentials::class)->getPassword());
        } catch (\Throwable) {
        }
    }

    private function checksAPassword(?Passport $passport): bool
    {
        return null !== $passport && $passport->hasBadge(PasswordCredentials::class) && $passport->hasBadge(UserBadge::class);
    }
}
