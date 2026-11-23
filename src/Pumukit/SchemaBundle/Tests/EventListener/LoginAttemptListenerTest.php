<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\Collection;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\EventListener\LoginAttemptListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\PasswordHasherFactoryInterface;
use Symfony\Component\PasswordHasher\PasswordHasherInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAccountStatusException;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginFailureEvent;

/**
 * @internal
 */
#[CoversNothing]
final class LoginAttemptListenerTest extends TestCase
{
    private const PASSWORD = 'any password';

    public function testAnUnknownUserSpendsTheTimeOfAPasswordCheck(): void
    {
        $exception = new BadCredentialsException('Bad credentials.', 0, new UserNotFoundException());

        $this->listener($this->hasher(1), $this->documentManager(0))->onLoginFailure($this->event($exception));
    }

    public function testABlockedAccountSpendsTheTimeOfAPasswordCheck(): void
    {
        $exception = new CustomUserMessageAccountStatusException('Invalid credentials or account temporarily blocked.');

        $this->listener($this->hasher(1), $this->documentManager(0))->onLoginFailure($this->event($exception));
    }

    public function testAWrongPasswordCountsTheAttemptWithoutHashingAgain(): void
    {
        $exception = new BadCredentialsException('The presented password is invalid.');

        $this->listener($this->hasher(0), $this->documentManager(1))->onLoginFailure($this->event($exception));
    }

    private function listener(PasswordHasherFactoryInterface $factory, DocumentManager $documentManager): LoginAttemptListener
    {
        return new LoginAttemptListener($documentManager, $factory);
    }

    private function hasher(int $hashes): PasswordHasherFactoryInterface
    {
        $hasher = $this->createMock(PasswordHasherInterface::class);
        $hasher->expects($this->exactly($hashes))->method('hash')->with(self::PASSWORD)->willReturn('hash');

        $factory = $this->createMock(PasswordHasherFactoryInterface::class);
        $factory->expects($this->exactly($hashes))->method('getPasswordHasher')->with(User::class)->willReturn($hasher);

        return $factory;
    }

    private function documentManager(int $updates): DocumentManager
    {
        $collection = $this->createMock(Collection::class);
        $collection->expects($this->exactly($updates))->method('updateOne');

        $documentManager = $this->createMock(DocumentManager::class);
        $documentManager->expects($this->exactly($updates))->method('getDocumentCollection')->with(User::class)->willReturn($collection);

        return $documentManager;
    }

    private function event(AuthenticationException $exception): LoginFailureEvent
    {
        $passport = new Passport(new UserBadge('someone'), new PasswordCredentials(self::PASSWORD));

        return new LoginFailureEvent($exception, $this->createStub(AuthenticatorInterface::class), new Request(), null, 'main', $passport);
    }
}
