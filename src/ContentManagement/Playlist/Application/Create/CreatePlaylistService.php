<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Create;

use App\ContentManagement\Playlist\Domain\Event\PlaylistCreatedEvent;
use App\ContentManagement\Playlist\Domain\Factory\PlaylistFactoryInterface;
use App\IdentityAndAccess\User\Domain\ValueObject\UserId;
use App\Shared\Domain\EventBusInterface;

final class CreatePlaylistService
{
    public function __construct(
        private PlaylistFactoryInterface $playlistFactory,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreatePlaylistRequest $request): CreatePlaylistResponse
    {
        CreatePlaylistValidator::validate($request);

        $userId = UserId::fromString($request->ownerId);

        $title = $request->title ?? ['es' => 'New Playlist', 'en' => 'New Playlist'];

        $playlist = $this->playlistFactory->createForUser($userId, $title);

        $this->eventBus->dispatch(new PlaylistCreatedEvent($playlist));

        return new CreatePlaylistResponse($playlist);
    }
}
