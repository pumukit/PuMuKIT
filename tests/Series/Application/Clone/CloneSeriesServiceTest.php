<?php

declare(strict_types=1);

namespace Tests\Series\Application\Clone;

use App\Series\Application\Clone\CloneSeriesRequest;
use App\Series\Application\Clone\CloneSeriesResponse;
use App\Series\Application\Clone\CloneSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Services\FactoryService;

final class CloneSeriesServiceTest extends TestCase
{
    private SeriesRepositoryInterface $repository;
    private FactoryService $factoryService;
    private CloneSeriesService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(SeriesRepositoryInterface::class);
        $this->factoryService = $this->createMock(FactoryService::class);
        $this->service = new CloneSeriesService($this->repository, $this->factoryService);
    }

    public function testItClonesSeriesWithMultimediaObjects(): void
    {
        // Mock Series to have an ID
        $originalSeries = $this->createMock(Series::class);
        $originalSeries->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $clonedSeries = $this->createMock(Series::class);

        $mm1 = $this->createMock(MultimediaObject::class);
        $mm2 = $this->createMock(MultimediaObject::class);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($originalSeries);

        // Mock findMultimediaObjectsBySeries to return the multimedia objects
        $this->repository
            ->expects($this->once())
            ->method('findMultimediaObjectsBySeries');
        $this->factoryService
            ->expects($this->once())
            ->method('cloneSeries')
            ->with($originalSeries)
            ->willReturn($clonedSeries);

        $this->factoryService
            ->expects($this->exactly(2))
            ->method('cloneMultimediaObject')
            ->withConsecutive(
                [$mm1, $clonedSeries],
                [$mm2, $clonedSeries]
            );

        $this->repository
            ->expects($this->once())
            ->method('save')
            ->with($clonedSeries);

        $request = new CloneSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(CloneSeriesResponse::class, $response);
        $this->assertSame($clonedSeries, $response->clonedSeries);
        $this->assertSame(2, $response->multimediaObjectsCloned);
    }

    public function testItThrowsExceptionWhenSeriesNotFound(): void
    {
        $this->expectException(SeriesNotFoundException::class);
        $this->expectExceptionMessage("Series with id '507f1f77bcf86cd799439011' not found");

        $this->repository
            ->method('find')
            ->willReturn(null);

        $request = new CloneSeriesRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptySeriesId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series ID cannot be empty');

        $request = new CloneSeriesRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSeriesIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Series ID format');

        $request = new CloneSeriesRequest('invalid-id');
        ($this->service)($request);
    }
}

