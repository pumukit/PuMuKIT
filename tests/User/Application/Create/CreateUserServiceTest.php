<?php

declare(strict_types=1);

namespace Tests\User\Application\Create;

use App\IdentityAndAccess\Application\Create\CreateUserRequest;
use App\IdentityAndAccess\Application\Create\CreateUserResponse;
use App\IdentityAndAccess\Application\Create\CreateUserService;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class CreateUserServiceTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private UserPasswordHasherInterface $passwordHasher;
    private CreateUserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->service = new CreateUserService($this->repository, $this->passwordHasher);
    }

    public function testItCreatesUserWithMinimalData(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($this->callback(function (User $user) {
                return $user->getUsername() === 'testuser'
                    && $user->getEmail() === 'test@example.com'
                    && !$user->isEnabled()
                    && $user->getOrigin() === 'local';
            }));

        $request = new CreateUserRequest(
            username: 'testuser',
            email: 'test@example.com'
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(CreateUserResponse::class, $response);
        $this->assertInstanceOf(User::class, $response->user);
    }

    public function testItValidatesEmptyUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username cannot be empty');

        $request = new CreateUserRequest(
            username: '',
            email: 'test@example.com'
        );

        ($this->service)($request);
    }

    public function testItValidatesShortUsername(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Username must be at least 3 characters long');

        $request = new CreateUserRequest(
            username: 'ab',
            email: 'test@example.com'
        );

        ($this->service)($request);
    }

    public function testItValidatesEmptyEmail(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Email cannot be empty');

        $request = new CreateUserRequest(
            username: 'testuser',
            email: ''
        );

        ($this->service)($request);
    }

    public function testItValidatesInvalidEmailFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid email format');

        $request = new CreateUserRequest(
            username: 'testuser',
            email: 'invalid-email'
        );

        ($this->service)($request);
    }

    public function testItValidatesInvalidOrigin(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid origin');

        $request = new CreateUserRequest(
            username: 'testuser',
            email: 'test@example.com',
            origin: 'invalid_origin'
        );

        ($this->service)($request);
    }
}

