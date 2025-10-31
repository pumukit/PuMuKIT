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

