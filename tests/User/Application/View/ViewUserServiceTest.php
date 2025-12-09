<?php

declare(strict_types=1);

namespace Tests\User\Application\View;

use App\IdentityAndAccess\Application\Find\FindUserService;
use App\IdentityAndAccess\Application\View\ViewUserRequest;
use App\IdentityAndAccess\Application\View\ViewUserResponse;
use App\IdentityAndAccess\Application\View\ViewUserService;
use App\IdentityAndAccess\Domain\Exception\UserNotFoundException;
use App\IdentityAndAccess\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;

final class ViewUserServiceTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private FindUserService $findUserService;
    private ViewUserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->findUserService = new FindUserService($this->repository);
        $this->service = new ViewUserService($this->findUserService);
    }

    public function testItViewsExistingUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('507f1f77bcf86cd799439011');
        $user->method('getUsername')->willReturn('testuser');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($user);

        $request = new ViewUserRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(ViewUserResponse::class, $response);
        $this->assertSame($user, $response->user);
    }

    public function testItThrowsExceptionWhenUserNotFound(): void
    {
        $this->expectException(UserNotFoundException::class);
        $this->expectExceptionMessage("User with id '507f1f77bcf86cd799439011' not found");

        $this->repository
            ->method('find')
            ->willReturn(null);

        $request = new ViewUserRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptyUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');

        $request = new ViewUserRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidUserIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid User ID format');

        $request = new ViewUserRequest('invalid-id');
        ($this->service)($request);
    }
}

