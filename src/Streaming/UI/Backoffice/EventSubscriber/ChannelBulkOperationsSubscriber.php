<?php

declare(strict_types=1);

namespace App\Streaming\UI\Backoffice\EventSubscriber;

use App\Streaming\UI\Backoffice\Event\ChannelBulkOperationsEvent;
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
            label: 'Eliminar',
            handler: 'streaming_channels_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: '¿Está seguro de que desea eliminar los canales seleccionados? Esta acción no se puede deshacer.',
            priority: 100
        );
    }
}
