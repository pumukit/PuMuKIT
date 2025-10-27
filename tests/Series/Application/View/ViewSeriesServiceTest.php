<?php

declare(strict_types=1);

namespace Tests\Series\Application\View;

use App\Series\Application\Find\FindSeriesRequest;
use App\Series\Application\Find\FindSeriesResponse;
use App\Series\Application\Find\FindSeriesService;
use App\Series\Application\View\ViewSeriesRequest;
use App\Series\Application\View\ViewSeriesResponse;
use App\Series\Application\View\ViewSeriesService;
use App\Series\Domain\Exception\SeriesNotFoundException;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;

final class ViewSeriesServiceTest extends TestCase
{
    private FindSeriesService $findSeriesService;
    private ViewSeriesService $service;

    protected function setUp(): void
    {
        $this->findSeriesService = $this->createMock(FindSeriesService::class);
        $this->service = new ViewSeriesService($this->findSeriesService);
    }

    public function testItViewsExistingSeries(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $findResponse = new FindSeriesResponse($series);

        $this->findSeriesService
            ->expects($this->once())
            ->method('__invoke')
            ->with($this->callback(fn($req) => $req instanceof FindSeriesRequest && $req->id === '507f1f77bcf86cd799439011'))
            ->willReturn($findResponse);

        $request = new ViewSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(ViewSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItThrowsExceptionWhenSeriesNotFound(): void
    {
        $this->expectException(SeriesNotFoundException::class);

        $this->findSeriesService
            ->expects($this->once())
            ->method('__invoke')
            ->willThrowException(new SeriesNotFoundException('507f1f77bcf86cd799439011'));

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

