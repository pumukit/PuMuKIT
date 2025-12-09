<?php

declare(strict_types=1);

namespace Tests\User\Application\Find;

use App\IdentityAndAccess\Application\Find\FindUserRequest;
use App\IdentityAndAccess\Application\Find\FindUserResponse;
use App\IdentityAndAccess\Application\Find\FindUserService;
use App\IdentityAndAccess\Domain\Exception\UserNotFoundException;
use App\IdentityAndAccess\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;

final class FindUserServiceTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private FindUserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new FindUserService($this->repository);
    }

    public function testItFindsExistingUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('507f1f77bcf86cd799439011');
        $user->method('getUsername')->willReturn('testuser');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($user);

        $request = new FindUserRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(FindUserResponse::class, $response);
        $this->assertSame($user, $response->user);
    }

    public function testItThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with id '507f1f77bcf86cd799439011' not found");

        $this->repository
            ->method('find')
            ->willReturn(null);

        $request = new FindUserRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptyUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');

        $request = new FindUserRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidUserIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid User ID format');

        $request = new FindUserRequest('invalid-id');
        ($this->service)($request);
    }
}

