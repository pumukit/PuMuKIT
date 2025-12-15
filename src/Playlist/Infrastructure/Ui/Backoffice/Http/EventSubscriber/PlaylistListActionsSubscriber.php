<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Playlist\Infrastructure\Ui\Backoffice\Http\Event\PlaylistListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PlaylistListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PlaylistListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(PlaylistListActionsEvent $event): void
    {
        $event->addAction(
            key: 'playlist_create',
            label: 'Create',
            url: 'playlist_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
