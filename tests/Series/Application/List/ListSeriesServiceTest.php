<?php

declare(strict_types=1);

namespace Tests\Series\Application\List;

use App\Series\Application\List\ListSeriesRequest;
use App\Series\Application\List\ListSeriesResponse;
use App\Series\Application\List\ListSeriesService;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;

final class ListSeriesServiceTest extends TestCase
{
    private SeriesRepositoryInterface $repository;
    private ListSeriesService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SeriesRepositoryInterface::class);
        $this->service = new ListSeriesService($this->repository);
    }

    public function testItListsSeriesWithPagination(): void
    {
        $series1 = $this->createMock(Series::class);
        $series2 = $this->createMock(Series::class);

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersPaginated')
            ->with([], 1, 10, 'title', 'asc')
            ->willReturn([$series1, $series2]);

        $this->repository
            ->expects($this->once())
            ->method('countByFilters')
            ->with([])
            ->willReturn(2);

        $request = new ListSeriesRequest(
            page: 1,
            limit: 10,
            filters: [],
            sort: 'title',
            order: 'asc'
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(ListSeriesResponse::class, $response);
        $this->assertCount(2, $response->series);
        $this->assertEquals(2, $response->total);
    }

    public function testItListsSeriesWithFilters(): void
    {
        $filters = ['title.es' => 'test'];

        $this->repository
            ->expects($this->once())
            ->method('findByFiltersPaginated')
            ->with($filters, 1, 20, 'title', 'asc')
            ->willReturn([]);

        $this->repository
            ->expects($this->once())
            ->method('countByFilters')
            ->with($filters)
            ->willReturn(0);

        $request = new ListSeriesRequest(
            page: 1,
            limit: 20,
            filters: $filters,
            sort: 'title',
            order: 'asc'
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(ListSeriesResponse::class, $response);
        $this->assertEmpty($response->series);
        $this->assertEquals(0, $response->total);
    }

    public function testItValidatesNegativePage(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Page must be greater than 0');

        $request = new ListSeriesRequest(
            page: -1,
            limit: 10,
            filters: [],
            sort: 'title',
            order: 'asc'
        );

        ($this->service)($request);
    }

    public function testItValidatesNegativeLimit(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Limit must be greater than 0');

        $request = new ListSeriesRequest(
            page: 1,
            limit: -1,
            filters: [],
            sort: 'title',
            order: 'asc'
        );

        ($this->service)($request);
    }
}

