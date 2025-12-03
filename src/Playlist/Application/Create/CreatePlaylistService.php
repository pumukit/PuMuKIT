<?php

declare(strict_types=1);

namespace App\Playlist\Application\Create;

use App\Playlist\Domain\Event\PlaylistCreatedEvent;
use App\Playlist\Domain\Factory\PlaylistFactoryInterface;
use App\Shared\Domain\EventBusInterface;
use App\User\Domain\ValueObject\UserId;

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
