<?php

declare(strict_types=1);

namespace Tests\MultimediaObject\Application\Find;

use App\MultimediaObject\Application\Find\FindMultimediaObjectRequest;
use App\MultimediaObject\Application\Find\FindMultimediaObjectResponse;
use App\MultimediaObject\Application\Find\FindMultimediaObjectService;
use App\MultimediaObject\Domain\Exception\MultimediaObjectNotFoundException;
use App\MultimediaObject\Domain\MultimediaObjectRepositoryInterface;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\MultimediaObject;

final class FindMultimediaObjectServiceTest extends TestCase
{
    private MultimediaObjectRepositoryInterface $repository;
    private FindMultimediaObjectService $service;

    protected function setUp(): void
    {
        $this->repository = $this->createMock(MultimediaObjectRepositoryInterface::class);
        $this->service = new FindMultimediaObjectService($this->repository);
    }

    public function testItFindsExistingMultimediaObject(): void
    {
        $multimediaObject = $this->createMock(MultimediaObject::class);
        $multimediaObject->method('getId')->willReturn('507f1f77bcf86cd799439011');

        $this->repository
            ->expects($this->once())
            ->method('find')
            ->with('507f1f77bcf86cd799439011')
            ->willReturn($multimediaObject);

        $request = new FindMultimediaObjectRequest('507f1f77bcf86cd799439011');
        $response = ($this->service)($request);

        $this->assertInstanceOf(FindMultimediaObjectResponse::class, $response);
        $this->assertSame($multimediaObject, $response->multimediaObject);
    }

    public function testItThrowsExceptionWhenMultimediaObjectNotFound(): void
    {
        $this->expectException(MultimediaObjectNotFoundException::class);
        $this->expectExceptionMessage("Multimedia Object with id '507f1f77bcf86cd799439011' not found");

        $this->repository
            ->method('find')
            ->willReturn(null);

        $request = new FindMultimediaObjectRequest('507f1f77bcf86cd799439011');
        ($this->service)($request);
    }

    public function testItValidatesEmptyMultimediaObjectId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('MultimediaObject ID cannot be empty');

        $request = new FindMultimediaObjectRequest('');
        ($this->service)($request);
    }

    public function testItValidatesInvalidMultimediaObjectIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid MultimediaObject ID format');

        $request = new FindMultimediaObjectRequest('invalid-id');
        ($this->service)($request);
    }
}

