<?php

declare(strict_types=1);

namespace App\Streaming\Channel\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Streaming\Channel\Infrastructure\Ui\Backoffice\Http\Event\ChannelBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class ChannelBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ChannelBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(ChannelBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'backoffice_streaming_channels_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected channels? This action cannot be undone.',
            priority: 100
        );
    }
}
