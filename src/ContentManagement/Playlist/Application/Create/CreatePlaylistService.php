<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Create;

use App\ContentManagement\Playlist\Domain\Event\PlaylistCreatedEvent;
use App\ContentManagement\Playlist\Domain\Factory\PlaylistFactoryInterface;
use App\Shared\Domain\EventBusInterface;
use App\IdentityAndAccess\Domain\ValueObject\UserId;

final class CreatePlaylistService
{
    public function __construct(
        private PlaylistFactoryInterface $playlistFactory,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreatePlaylistRequest $request): CreatePlaylistResponse
    {
        $userId = UserId::fromString($request->ownerId);

        $title = $request->title ?? ['es' => 'New Playlist', 'en' => 'New Playlist'];

        $playlist = $this->playlistFactory->createForUser($userId, $title);

        $this->eventBus->dispatch(new PlaylistCreatedEvent($playlist));

        return new CreatePlaylistResponse($playlist);
    }
}
