<?php

declare(strict_types=1);

namespace Tests\Series\Application\ViewSeriesOwners;

use App\ContentManagement\Series\Application\ViewSeriesOwners\GetSeriesOwnersRequest;
use App\ContentManagement\Series\Application\ViewSeriesOwners\GetSeriesOwnersResponse;
use App\ContentManagement\Series\Application\ViewSeriesOwners\GetSeriesOwnersService;
use App\ContentManagement\Series\Domain\Exception\SeriesNotFoundException;
use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\User;

final class GetSeriesOwnersServiceTest extends TestCase
{
    private SeriesRepositoryInterface $seriesRepository;
    private UserRepositoryInterface $userRepository;
    private GetSeriesOwnersService $service;

    protected function setUp(): void
    {
        $this->seriesRepository = $this->createMock(SeriesRepositoryInterface::class);
        $this->userRepository = $this->createMock(UserRepositoryInterface::class);
        $this->service = new GetSeriesOwnersService(
            $this->seriesRepository,
            $this->userRepository
        );
    }

    public function testItGetsSeriesOwners(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getOwnerIds')->willReturn(['user1', 'user2']);

        $owner1 = $this->createMock(User::class);
        $owner2 = $this->createMock(User::class);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($series);

        $this->userRepository
            ->expects($this->once())
            ->method('findByIds')
            ->with(['user1', 'user2'])
            ->willReturn([$owner1, $owner2]);

        $request = new GetSeriesOwnersRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(GetSeriesOwnersResponse::class, $response);
        $this->assertCount(2, $response->owners);
    }

    public function testItReturnsEmptyArrayWhenNoOwners(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getOwnerIds')->willReturn([]);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn($series);

        $this->userRepository
            ->expects($this->never())
            ->method('findByIds');

        $request = new GetSeriesOwnersRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(GetSeriesOwnersResponse::class, $response);
        $this->assertEmpty($response->owners);
    }

    public function testItThrowsExceptionWhenSeriesNotFound(): void
    {
        $this->expectException(SeriesNotFoundException::class);

        $this->seriesRepository
            ->method('find')
            ->willReturn(null);

        $request = new GetSeriesOwnersRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptySeriesId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series ID cannot be empty');

        $request = new GetSeriesOwnersRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSeriesIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Series ID format');

        $request = new GetSeriesOwnersRequest('invalid-id');
        ($this->service)($request);
    }
}

