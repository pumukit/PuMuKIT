<?php

declare(strict_types=1);

namespace Tests\MultimediaObject\Application\View;

use App\MultimediaObject\Application\Find\FindMultimediaObjectService;
use App\MultimediaObject\Application\View\ViewMultimediaObjectRequest;
use App\MultimediaObject\Application\View\ViewMultimediaObjectResponse;
use App\MultimediaObject\Application\View\ViewMultimediaObjectService;
use App\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use App\MultimediaObject\Domain\MultimediaObjectRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class ViewMultimediaObjectServiceTest extends TestCase
{
    private MultimediaObjectRepositoryInterface $repository;
    private FindMultimediaObjectService $findService;
    private ViewMultimediaObjectService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MultimediaObjectRepositoryInterface::class);
        $this->findService = new FindMultimediaObjectService($this->repository);
        $this->service = new ViewMultimediaObjectService($this->findService);
    }

    public function testItViewsExistingMultimediaObject(): void
    {
        $multimediaObject = $this->createMock(MultimediaObject::class);
        $multimediaObject->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($multimediaObject);

        $request = new ViewMultimediaObjectRequest('507f1f77bcf86cd799439011', 'general');
        $response = ($this->service)($request);

        $this->assertInstanceOf(ViewMultimediaObjectResponse::class, $response);
        $this->assertSame($multimediaObject, $response->multimediaObject);
        $this->assertEquals('general', $response->tab);
    }

    public function testItViewsWithCustomTab(): void
    {
        $multimediaObject = $this->createMock(MultimediaObject::class);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($multimediaObject);

        $request = new ViewMultimediaObjectRequest('507f1f77bcf86cd799439011', 'media');
        $response = ($this->service)($request);

        $this->assertEquals('media', $response->tab);
    }

    public function testItThrowsExceptionWhenMultimediaObjectNotFound(): void
    {
        $this->expectException(MultimediaObjectNotFoundException::class);

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn(null);

        $request = new ViewMultimediaObjectRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptyMultimediaObjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MultimediaObject ID cannot be empty');

        $request = new ViewMultimediaObjectRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidMultimediaObjectIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MultimediaObject ID format');

        $request = new ViewMultimediaObjectRequest('invalid-id');
        ($this->service)($request);
    }

    public function testItValidatesInvalidTab(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid tab');

        $request = new ViewMultimediaObjectRequest('507f1f77bcf86cd799439011', 'invalid-tab');
        ($this->service)($request);
    }
}

