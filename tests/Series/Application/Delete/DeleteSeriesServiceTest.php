<?php

declare(strict_types=1);

namespace Tests\Series\Application\Delete;

use App\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\Series\Application\Delete\DeleteSeriesRequest;
use App\Series\Application\Delete\DeleteSeriesResponse;
use App\Series\Application\Delete\DeleteSeriesService;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteSeriesServiceTest extends TestCase
{
    private SeriesRepositoryInterface $seriesRepository;
    private MultimediaObjectRepositoryInterface $multimediaRepository;
    private EventDispatcherInterface $eventDispatcher;
    private DeleteSeriesService $service;

    protected function setUp(): void
    {
        $this->seriesRepository = $this->createMock(SeriesRepositoryInterface::class);
        $this->multimediaRepository = $this->createMock(MultimediaObjectRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new DeleteSeriesService(
            $this->seriesRepository,
            $this->multimediaRepository,
            $this->eventDispatcher
        );
    }

    public function testItDeletesSeriesWithMultimediaObjects(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn('507f1f77bcf86cd799439011');
        $series->method('getTitle')->willReturn('Test Series');

        $mm1 = $this->createMock(MultimediaObject::class);
        $mm2 = $this->createMock(MultimediaObject::class);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($series);

        $this->multimediaRepository
            ->expects($this->once())
            ->method('findBySeriesId')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn([$mm1, $mm2]);

        $this->multimediaRepository
            ->expects($this->exactly(2))
            ->method('delete');

        $this->eventDispatcher
            ->expects($this->exactly(3))
            ->method('dispatch');

        $this->seriesRepository
            ->expects($this->once())
            ->method('delete')
            ->with($series);

        $request = new DeleteSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(DeleteSeriesResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertStringContainsString('Test Series', $response->message);
    }

    public function testItReturnsErrorWhenSeriesNotFound(): void
    {
        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->multimediaRepository
            ->expects($this->never())
            ->method('findBySeriesId');

        $this->seriesRepository
            ->expects($this->never())
            ->method('delete');

        $request = new DeleteSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(DeleteSeriesResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('not found', $response->message);
    }

    public function testItValidatesEmptySeriesId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series ID cannot be empty');

        $request = new DeleteSeriesRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSeriesIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Series ID format');

        $request = new DeleteSeriesRequest('invalid-id');
        ($this->service)($request);
    }
}

