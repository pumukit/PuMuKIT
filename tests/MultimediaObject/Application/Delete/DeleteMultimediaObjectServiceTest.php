<?php

declare(strict_types=1);

namespace Tests\MultimediaObject\Application\Delete;

use App\ContentManagement\MultimediaObject\Application\Delete\DeleteMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\Delete\DeleteMultimediaObjectResponse;
use App\ContentManagement\MultimediaObject\Application\Delete\DeleteMultimediaObjectService;
use App\ContentManagement\MultimediaObject\Domain\Event\MultimediaObjectDeletedEvent;
use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteMultimediaObjectServiceTest extends TestCase
{
    private MultimediaObjectRepositoryInterface $repository;
    private EventDispatcherInterface $eventDispatcher;
    private DeleteMultimediaObjectService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MultimediaObjectRepositoryInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->service = new DeleteMultimediaObjectService(
            $this->repository,
            $this->eventDispatcher
        );
    }

    public function testItDeletesMultimediaObject(): void
    {
        // Create a mock Series - MultimediaObject requires an associated Series
        $series = $this->createMock(\Pumukit\SchemaBundle\Document\Series::class);
        $series->method('getId')->willReturn('607f1f77bcf86cd799439012');

        $multimediaObject = $this->createMock(MultimediaObject::class);
        $multimediaObject->method('getId')->willReturn('507f1f77bcf86cd799439011');
        $multimediaObject->method('getTitle')->willReturn('Test Video');
        $multimediaObject->method('getSeries')->willReturn($series);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($multimediaObject);

        $this->repository
            ->expects($this->once())
            ->method('delete')
            ->with($multimediaObject);

        $this->eventDispatcher
            ->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(MultimediaObjectDeletedEvent::class),
                MultimediaObjectDeletedEvent::NAME
            );

        $request = new DeleteMultimediaObjectRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(DeleteMultimediaObjectResponse::class, $response);
        $this->assertTrue($response->success);
        $this->assertStringContainsString('Test Video', $response->message);
        $this->assertStringContainsString('deleted successfully', $response->message);
        $this->assertEquals('607f1f77bcf86cd799439012', $response->series);
    }

    public function testItReturnsErrorWhenMultimediaObjectNotFound(): void
    {
        $this->repository
            ->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->repository
            ->expects($this->never())
            ->method('delete');

        $this->eventDispatcher
            ->expects($this->never())
            ->method('dispatch');

        $request = new DeleteMultimediaObjectRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(DeleteMultimediaObjectResponse::class, $response);
        $this->assertFalse($response->success);
        $this->assertStringContainsString('not found', $response->message);
    }

    public function testItValidatesEmptyMultimediaObjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MultimediaObject ID cannot be empty');

        $request = new DeleteMultimediaObjectRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidMultimediaObjectIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MultimediaObject ID format');

        $request = new DeleteMultimediaObjectRequest('invalid-id');
        ($this->service)($request);
    }

    public function testResponseToArray(): void
    {
        $response = new DeleteMultimediaObjectResponse(
            success: true,
            message: 'Test message',
            series: null
        );

        $array = $response->toArray();

        $this->assertIsArray($array);
        $this->assertTrue($array['success']);
        $this->assertEquals('Test message', $array['message']);
    }
}

