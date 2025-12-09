<?php

declare(strict_types=1);

namespace Tests\Series\Application\View;

use App\ContentManagement\Series\Application\Find\FindSeriesService;
use App\ContentManagement\Series\Application\View\ViewSeriesRequest;
use App\ContentManagement\Series\Application\View\ViewSeriesResponse;
use App\ContentManagement\Series\Application\View\ViewSeriesService;
use App\ContentManagement\Series\Domain\Exception\SeriesNotFoundException;
use App\ContentManagement\Series\Domain\Repository\SeriesRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;

final class ViewSeriesServiceTest extends TestCase
{
    private SeriesRepositoryInterface $repository;
    private FindSeriesService $findService;
    private ViewSeriesService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SeriesRepositoryInterface::class);
        $this->findService = new FindSeriesService($this->repository);
        $this->service = new ViewSeriesService($this->findService);
    }

    public function testItViewsExistingSeries(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($series);

        $request = new ViewSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(ViewSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItThrowsExceptionWhenSeriesNotFound(): void
    {
        $this->expectException(SeriesNotFoundException::class);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn(null);

        $request = new ViewSeriesRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptySeriesId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series ID cannot be empty');

        $request = new ViewSeriesRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSeriesIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Series ID format');

        $request = new ViewSeriesRequest('invalid-id');
        ($this->service)($request);
    }
}

