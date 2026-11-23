<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use PHPUnit\Framework\Attributes\CoversNothing;
use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PasswordService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 */
#[CoversNothing]
class PasswordServiceTest extends PumukitTestCase
{
    private PasswordService $passwordService;
    private UserPasswordHasherInterface $hasher;

    public function setUp(): void
    {
        $options = ['environment' => 'test'];
        static::bootKernel($options);
        parent::setUp();

        $this->hasher = static::getContainer()->get(UserPasswordHasherInterface::class);
        $this->passwordService = new PasswordService($this->dm, $this->hasher);
    }

    public function testChangePasswordReplacesUserPasswordWithHash(): void
    {
        $user = new User();
        $user->setUsername('pwtest');
        $user->setEmail('pwtest@example.com');
        $user->setPassword('original-plaintext');
        $this->dm->persist($user);
        $this->dm->flush();

        $this->passwordService->changePassword($user, 'newSecret123');

        $this->assertNotSame('newSecret123', $user->getPassword(), 'Stored password must be a hash, not the plaintext');
        $this->assertTrue($this->hasher->isPasswordValid($user, 'newSecret123'));
        $this->assertFalse($this->hasher->isPasswordValid($user, 'original-plaintext'));
    }

    public function testAFailedSaveKeepsTheOriginalError(): void
    {
        $documentManager = $this->createStub(DocumentManager::class);
        $failure = new \RuntimeException('The database is not available');
        $documentManager->method('flush')->willThrowException($failure);
        $user = new User();
        $user->setUsername('pwtest');

        try {
            (new PasswordService($documentManager, $this->hasher))->changePassword($user, 'newSecret123');
            $this->fail('The failed save must throw');
        } catch (\Exception $exception) {
            $this->assertSame('The database is not available', $exception->getMessage());
            $this->assertSame($failure, $exception->getPrevious());
        }
    }
}
