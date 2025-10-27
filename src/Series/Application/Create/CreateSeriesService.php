<?php

declare(strict_types=1);

namespace App\Series\Application\Create;

use App\Series\Domain\Event\SeriesCreatedEvent;
use App\Series\Domain\SeriesFactoryInterface;
use App\Shared\Domain\EventBusInterface;
use App\User\Domain\ValueObject\UserId;

final class CreateSeriesService
{
    public function __construct(
        private SeriesFactoryInterface $seriesFactory,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateSeriesRequest $request): CreateSeriesResponse
    {
        CreateSeriesValidator::validate($request);

        $userId = UserId::fromString($request->ownerId);

        $title = $request->title ?? ['es' => 'New', 'en' => 'New'];

        $series = $this->seriesFactory->createForUser($userId, $title);

        $this->eventBus->dispatch(new SeriesCreatedEvent($series));

        return new CreateSeriesResponse($series);
    }
}

