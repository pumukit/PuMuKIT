<?php

declare(strict_types=1);

namespace Tests\User\Application\Delete;

use App\IdentityAndAccess\Application\Delete\DeleteUserRequest;
use App\IdentityAndAccess\Application\Delete\DeleteUserResponse;
use App\IdentityAndAccess\Application\Delete\DeleteUserService;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;

final class DeleteUserServiceTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private DeleteUserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new DeleteUserService($this->repository);
    }

    public function testItDeletesExistingUser(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn('507f1f77bcf86cd799439011');
        $user->method('getUsername')->willReturn('testuser');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($user);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($user);

        $request = new DeleteUserRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(DeleteUserResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertStringContainsString('testuser', $response->message);
        $this->assertStringContainsString('deleted successfully', $response->message);
    }

    public function testItReturnsFailureWhenUserNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('delete');

        $request = new DeleteUserRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(DeleteUserResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('not found', $response->message);
        $this->assertStringContainsString('507f1f77bcf86cd799439011', $response->message);
    }

    public function testItValidatesEmptyUserId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('User ID cannot be empty');

        $request = new DeleteUserRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidUserIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid User ID format');

        $request = new DeleteUserRequest('invalid-id');
        ($this->service)($request);
    }

    public function testDeleteResponseWithSuccessMessage(): void
    {
        $response = new DeleteUserResponse(
            success: true,
            message: 'User deleted successfully'
        );

        $this->assertTrue($response->success);
        $this->assertEquals('User deleted successfully', $response->message);
    }

    public function testDeleteResponseWithFailureMessage(): void
    {
        $response = new DeleteUserResponse(
            success: false,
            message: 'User not found'
        );

        $this->assertFalse($response->success);
        $this->assertEquals('User not found', $response->message);
    }
}

