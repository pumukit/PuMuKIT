<?php

declare(strict_types=1);

namespace Tests\User\Application\List;

use App\IdentityAndAccess\Application\List\ListUserRequest;
use App\IdentityAndAccess\Application\List\ListUserResponse;
use App\IdentityAndAccess\Application\List\ListUserService;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\User;

final class ListUserServiceTest extends TestCase
{
    private UserRepositoryInterface $repository;
    private ListUserService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new ListUserService($this->repository);
    }

    public function testItListsUsersWithDefaultParameters(): void
    {
        $user1 = $this->createMock(User::class);
        $user1->method('getUsername')->willReturn('user1');
        $user2 = $this->createMock(User::class);
        $user2->method('getUsername')->willReturn('user2');

        $users = [$user1, $user2];

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersPaginated')
            ->with([], 1, 20, 'username', 'asc')
            ->willReturn($users);

        $this->repository
            ->expects($this->once())
            ->method('countByFilters')
            ->with([])
            ->willReturn(2);

        $request = new ListUserRequest();
        $response = ($this->service)($request);

        $this->assertInstanceOf(ListUserResponse::class, $response);
        $this->assertCount(2, $response->users);
        $this->assertEquals(2, $response->total);
    }

    public function testItListsUsersWithCustomPagination(): void
    {
        $users = [$this->createMock(User::class)];

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersPaginated')
            ->with([], 2, 10, 'username', 'asc')
            ->willReturn($users);

        $this->repository
            ->expects($this->once())
            ->method('countByFilters')
            ->willReturn(15);

        $request = new ListUserRequest(page: 2, limit: 10);
        $response = ($this->service)($request);

        $this->assertInstanceOf(ListUserResponse::class, $response);
        $this->assertEquals(15, $response->total);
    }

    public function testItListsUsersWithFilters(): void
    {
        $users = [$this->createMock(User::class)];
        $filters = ['enabled' => true, 'origin' => 'local'];

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersPaginated')
            ->with($filters, 1, 20, 'username', 'asc')
            ->willReturn($users);

        $this->repository
            ->expects($this->once())
            ->method('countByFilters')
            ->with($filters)
            ->willReturn(1);

        $request = new ListUserRequest(filters: $filters);
        $response = ($this->service)($request);

        $this->assertCount(1, $response->users);
        $this->assertEquals(1, $response->total);
    }

    public function testItListsUsersWithCustomSortAndOrder(): void
    {
        $users = [];

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersPaginated')
            ->with([], 1, 20, 'email', 'desc')
            ->willReturn($users);

        $this->repository
            ->method('countByFilters')
            ->willReturn(0);

        $request = new ListUserRequest(sort: 'email', order: 'desc');
        $response = ($this->service)($request);

        $this->assertEmpty($response->users);
        $this->assertEquals(0, $response->total);
    }

    public function testItValidatesInvalidPage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be greater than 0');

        $request = new ListUserRequest(page: 0);
        ($this->service)($request);
    }

    public function testItValidatesInvalidLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be greater than 0');

        $request = new ListUserRequest(limit: 0);
        ($this->service)($request);
    }

    public function testItValidatesInvalidSortField(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sort field');

        $request = new ListUserRequest(sort: 'invalid_field');
        ($this->service)($request);
    }

    public function testItValidatesInvalidOrder(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid order');

        $request = new ListUserRequest(order: 'invalid');
        ($this->service)($request);
    }
}

