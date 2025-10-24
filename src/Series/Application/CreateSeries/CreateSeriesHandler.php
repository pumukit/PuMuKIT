<?php

namespace App\Series\Application\CreateSeries;

use App\Series\Domain\Events\SeriesCreatedEvent;
use App\Series\Domain\SeriesFactoryInterface;
use App\Shared\Domain\EventBusInterface;
use App\User\Domain\ValueObject\UserId;

final class CreateSeriesHandler
{
    public function __construct(
        private SeriesFactoryInterface $seriesFactory,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateSeriesCommand $command): CreateSeriesResponse
    {
        $userId = UserId::fromString($command->ownerId);

        $title = $command->title ?? ['es' => 'New', 'en' => 'New'];

        $series = $this->seriesFactory->createForUser($userId, $title);

        $this->eventBus->dispatch(new SeriesCreatedEvent($series->getId(), $userId));

        return new CreateSeriesResponse($series->getId(), $title, $userId->toObjectId());
    }
}
