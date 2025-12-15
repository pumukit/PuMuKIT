<?php

declare(strict_types=1);

namespace App\Playlist\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Playlist\Infrastructure\Ui\Backoffice\Http\Event\PlaylistBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PlaylistBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PlaylistBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(PlaylistBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'playlist_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            priority: 10
        );
    }
}
