<?php

declare(strict_types=1);

namespace Tests\Series\Application\Update;

use App\Series\Application\Update\UpdateSeriesRequest;
use App\Series\Application\Update\UpdateSeriesResponse;
use App\Series\Application\Update\UpdateSeriesService;
use App\Series\Domain\Event\SeriesUpdatedEvent;
use App\Series\Domain\Exception\SeriesNotFoundException;
use App\Series\Domain\Repository\SeriesRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\Repository\DocumentRepository;
use PHPUnit\Framework\TestCase;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\SchemaBundle\Document\SeriesStyle;
use Pumukit\SchemaBundle\Document\SeriesType;

final class UpdateSeriesServiceTest extends TestCase
{
    private SeriesRepositoryInterface $seriesRepository;
    private DocumentManager $documentManager;
    private EventBusInterface $eventBus;
    private UpdateSeriesService $service;

    protected function setUp(): void
    {
        $this->seriesRepository = $this->createMock(SeriesRepositoryInterface::class);
        $this->documentManager = $this->createMock(DocumentManager::class);
        $this->eventBus = $this->createMock(EventBusInterface::class);
        $this->service = new UpdateSeriesService(
            $this->seriesRepository,
            $this->documentManager,
            $this->eventBus
        );
    }

    public function testItUpdatesSeriesTitle(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $newTitle = ['es' => 'Título actualizado', 'en' => 'Updated Title'];

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);
        $series->expects($this->once())
            ->method('setI18nTitle')
            ->with($newTitle);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->seriesRepository
            ->expects($this->once())
            ->method('save')
            ->with($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SeriesUpdatedEvent::class));

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            title: $newTitle
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(UpdateSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItUpdatesAllFields(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $newTitle = ['es' => 'Título', 'en' => 'Title'];
        $newSubtitle = ['es' => 'Subtítulo', 'en' => 'Subtitle'];
        $newDescription = ['es' => 'Descripción', 'en' => 'Description'];
        $newHeader = ['es' => 'Cabecera', 'en' => 'Header'];
        $newFooter = ['es' => 'Pie', 'en' => 'Footer'];
        $newComments = 'Test comments';
        $newKeywords = ['es' => 'palabra1, palabra2', 'en' => 'keyword1, keyword2'];
        $newPublicDate = new \DateTime('2024-01-01');

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);
        $series->expects($this->once())->method('setI18nTitle')->with($newTitle);
        $series->expects($this->once())->method('setI18nSubtitle')->with($newSubtitle);
        $series->expects($this->once())->method('setI18nDescription')->with($newDescription);
        $series->expects($this->once())->method('setI18nHeader')->with($newHeader);
        $series->expects($this->once())->method('setI18nFooter')->with($newFooter);
        $series->expects($this->once())->method('setComments')->with($newComments);
        $series->expects($this->once())->method('setI18nKeywords')->with($newKeywords);
        $series->expects($this->once())->method('setAnnounce')->with(true);
        $series->expects($this->once())->method('setHide')->with(false);
        $series->expects($this->once())->method('setPublicDate')->with($newPublicDate);
        $series->expects($this->once())->method('setSorting')->with(1);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->seriesRepository
            ->expects($this->once())
            ->method('save')
            ->with($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch')
            ->with($this->isInstanceOf(SeriesUpdatedEvent::class));

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            title: $newTitle,
            subtitle: $newSubtitle,
            description: $newDescription,
            header: $newHeader,
            footer: $newFooter,
            comments: $newComments,
            keywords: $newKeywords,
            announce: true,
            hide: false,
            publicDate: $newPublicDate,
            sorting: 1
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(UpdateSeriesResponse::class, $response);
        $this->assertSame($series, $response->series);
    }

    public function testItUpdatesSeriesType(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $seriesTypeId = '507f1f77bcf86cd799439022';

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);

        $seriesType = $this->createMock(SeriesType::class);

        $seriesTypeRepository = $this->createMock(DocumentRepository::class);
        $seriesTypeRepository->expects($this->once())
            ->method('find')
            ->with($seriesTypeId)
            ->willReturn($seriesType);

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(SeriesType::class)
            ->willReturn($seriesTypeRepository);

        $series->expects($this->once())
            ->method('setSeriesType')
            ->with($seriesType);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->seriesRepository
            ->expects($this->once())
            ->method('save')
            ->with($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch');

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            seriesTypeId: $seriesTypeId
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(UpdateSeriesResponse::class, $response);
    }

    public function testItUpdatesSeriesStyle(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $seriesStyleId = '507f1f77bcf86cd799439033';

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);

        $seriesStyle = $this->createMock(SeriesStyle::class);

        $seriesStyleRepository = $this->createMock(DocumentRepository::class);
        $seriesStyleRepository->expects($this->once())
            ->method('find')
            ->with($seriesStyleId)
            ->willReturn($seriesStyle);

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(SeriesStyle::class)
            ->willReturn($seriesStyleRepository);

        $series->expects($this->once())
            ->method('setSeriesStyle')
            ->with($seriesStyle);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->seriesRepository
            ->expects($this->once())
            ->method('save')
            ->with($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch');

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            seriesStyleId: $seriesStyleId
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(UpdateSeriesResponse::class, $response);
    }

    public function testItUpdatesProperties(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $properties = ['custom_key' => 'custom_value', 'another_key' => 'another_value'];

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);
        $series->expects($this->exactly(2))
            ->method('setProperty')
            ->withConsecutive(
                ['custom_key', 'custom_value'],
                ['another_key', 'another_value']
            );

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->seriesRepository
            ->expects($this->once())
            ->method('save')
            ->with($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch');

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            properties: $properties
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(UpdateSeriesResponse::class, $response);
    }

    public function testItRemovesPropertiesWhenNull(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $properties = ['key_to_remove' => null, 'key_to_keep' => 'value'];

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);
        $series->expects($this->once())
            ->method('removeProperty')
            ->with('key_to_remove');
        $series->expects($this->once())
            ->method('setProperty')
            ->with('key_to_keep', 'value');

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->seriesRepository
            ->expects($this->once())
            ->method('save')
            ->with($series);

        $this->eventBus
            ->expects($this->once())
            ->method('dispatch');

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            properties: $properties
        );

        $response = ($this->service)($request);

        $this->assertInstanceOf(UpdateSeriesResponse::class, $response);
    }

    public function testItThrowsExceptionWhenSeriesNotFound(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn(null);

        $this->expectException(SeriesNotFoundException::class);

        $request = new UpdateSeriesRequest(id: $seriesId);
        ($this->service)($request);
    }

    public function testItThrowsExceptionWhenSeriesTypeNotFound(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $seriesTypeId = '507f1f77bcf86cd799439022';

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);

        $seriesTypeRepository = $this->createMock(DocumentRepository::class);
        $seriesTypeRepository->expects($this->once())
            ->method('find')
            ->with($seriesTypeId)
            ->willReturn(null);

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(SeriesType::class)
            ->willReturn($seriesTypeRepository);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series Type with ID 507f1f77bcf86cd799439022 not found');

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            seriesTypeId: $seriesTypeId
        );

        ($this->service)($request);
    }

    public function testItThrowsExceptionWhenSeriesStyleNotFound(): void
    {
        $seriesId = '507f1f77bcf86cd799439011';
        $seriesStyleId = '507f1f77bcf86cd799439033';

        $series = $this->createMock(Series::class);
        $series->method('getId')->willReturn($seriesId);

        $seriesStyleRepository = $this->createMock(DocumentRepository::class);
        $seriesStyleRepository->expects($this->once())
            ->method('find')
            ->with($seriesStyleId)
            ->willReturn(null);

        $this->documentManager->expects($this->once())
            ->method('getRepository')
            ->with(SeriesStyle::class)
            ->willReturn($seriesStyleRepository);

        $this->seriesRepository
            ->expects($this->once())
            ->method('find')
            ->with($seriesId)
            ->willReturn($series);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series Style with ID 507f1f77bcf86cd799439033 not found');

        $request = new UpdateSeriesRequest(
            id: $seriesId,
            seriesStyleId: $seriesStyleId
        );

        ($this->service)($request);
    }

    public function testItValidatesEmptySeriesId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Series ID cannot be empty');

        $request = new UpdateSeriesRequest(id: '');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSeriesIdFormat(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid Series ID format');

        $request = new UpdateSeriesRequest(id: 'invalid-id');
        ($this->service)($request);
    }

    public function testItValidatesInvalidSortingValue(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid sorting value');

        $request = new UpdateSeriesRequest(
            id: '507f1f77bcf86cd799439011',
            sorting: 99
        );
        ($this->service)($request);
    }


    public function testItValidatesEmptyTitleArray(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Title array cannot be empty');

        $request = new UpdateSeriesRequest(
            id: '507f1f77bcf86cd799439011',
            title: []
        );
        ($this->service)($request);
    }
}

