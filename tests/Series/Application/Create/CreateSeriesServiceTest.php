<?php

declare(strict_types=1);

namespace Tests\Series\Application\Create;

use App\ContentManagement\Series\Application\Create\CreateSeriesRequest;
use App\ContentManagement\Series\Application\Create\CreateSeriesResponse;
use App\ContentManagement\Series\Application\Create\CreateSeriesService;
use App\ContentManagement\Series\Domain\Event\SeriesCreatedEvent;
use App\ContentManagement\Series\Domain\Factory\SeriesFactoryInterface;
use App\Shared\Domain\EventBusInterface;
use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;

final class CreateSeriesServiceTest extends TestCase
{
    private SeriesFactoryInterface $seriesFactory;
    private EventBusInterface $eventBus;
    private CreateSeriesService $service;

    protected function setUp(): void
    {
        $this->seriesFactory = $this->createMock(SeriesFactoryInterface::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->service = new CreateSeriesService($this->seriesFactory, $this->eventBus);
    }

    public function testItCreatesSeriesWithDefaultTitle(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $this->seriesFactory
            ->expects($this->once())
            ->method('createForUser')
            ->with(
                $this->callback(fn($userId) => $userId instanceof UserId),
                ['es' => 'New', 'en' => 'New']
            )
            ->willReturn($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SeriesCreatedEvent::class));

        $request = new CreateSeriesRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(CreateSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItCreatesSeriesWithCustomTitle(): void
    {
        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $customTitle = ['es' => 'Mi Serie', 'en' => 'My Series'];

        $this->seriesFactory
            ->expects($this->once())
            ->method('createForUser')
            ->with(
                $this->callback(fn($userId) => $userId instanceof UserId),
                $customTitle
            )
            ->willReturn($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch');

        $request = new CreateSeriesRequest('507f1f77bcf86cd799439011', $customTitle);
        $response = ($this->service)($request);

        $this->assertInstanceOf(CreateSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItValidatesEmptyOwnerId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Owner ID cannot be empty');

        $request = new CreateSeriesRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidOwnerIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Owner ID format');

        $request = new CreateSeriesRequest('invalid-id');
        ($this->service)($request);
    }

    public function testItValidatesEmptyTitleArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title array cannot be empty');

        $request = new CreateSeriesRequest('507f1f77bcf86cd799439011', []);
        ($this->service)($request);
    }
}

