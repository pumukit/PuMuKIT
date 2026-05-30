<?php

declare(strict_types=1);

namespace Pumukit\SchemaBundle\Tests\Services;

use Pumukit\CoreBundle\Tests\PumukitTestCase;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\SchemaBundle\Services\PasswordService;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

/**
 * @internal
 *
 * @coversNothing
 */
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
}
