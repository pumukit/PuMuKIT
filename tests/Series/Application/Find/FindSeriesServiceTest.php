<?php

declare(strict_types=1);

namespace Tests\Series\Application\Find;

use App\Series\Application\Find\FindSeriesRequest;
use App\Series\Application\Find\FindSeriesResponse;
use App\Series\Application\Find\FindSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\SeriesRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;

final class FindSeriesServiceTest extends TestCase
{
    private SeriesRepositoryInterface $repository;
    private FindSeriesService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SeriesRepositoryInterface::class);
        $this->service = new FindSeriesService($this->repository);
    }

    public function testItFindsExistingSeries(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($series);

        $request = new FindSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(FindSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItThrowsExceptionWhenSeriesNotFound(): void
    {
        $this->expectException(SeriesNotFoundException::class);
        $this->expectExceptionMessage("Series with id '507f1f77bcf86cd799439011' not found");

        $this->repository
            ->method('find')
            ->willReturn(null);

        $request = new FindSeriesRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptySeriesId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series ID cannot be empty');

        $request = new FindSeriesRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSeriesIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Series ID format');

        $request = new FindSeriesRequest('invalid-id');
        ($this->service)($request);
    }
}

