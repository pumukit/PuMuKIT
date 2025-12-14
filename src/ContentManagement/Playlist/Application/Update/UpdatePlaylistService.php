<?php

declare(strict_types=1);

namespace App\ContentManagement\Playlist\Application\Update;

use App\ContentManagement\Playlist\Domain\Event\PlaylistUpdatedEvent;
use App\ContentManagement\Playlist\Domain\Exception\PlaylistNotFoundException;
use App\ContentManagement\Playlist\Domain\Repository\PlaylistRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class UpdatePlaylistService
{
    public function __construct(
        private readonly PlaylistRepositoryInterface $playlistRepository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdatePlaylistRequest $request): UpdatePlaylistResponse
    {
        UpdatePlaylistValidator::validate($request);

        $playlist = $this->playlistRepository->find($request->id);

        if (!$playlist) {
            throw new PlaylistNotFoundException($request->id);
        }

        if (null !== $request->title) {
            $playlist->setI18nTitle($request->title);
        }

        if (null !== $request->description) {
            $playlist->setI18nDescription($request->description);
        }

        $this->playlistRepository->save($playlist);

        $this->eventBus->dispatch(new PlaylistUpdatedEvent($playlist));

        return new UpdatePlaylistResponse($playlist);
    }
}
