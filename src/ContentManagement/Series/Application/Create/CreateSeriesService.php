<?php

declare(strict_types=1);

namespace App\ContentManagement\Series\Application\Create;

use App\ContentManagement\Series\Domain\Event\SeriesCreatedEvent;
use App\ContentManagement\Series\Domain\Factory\SeriesFactoryInterface;
use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
use App\Shared\Domain\EventBusInterface;

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
