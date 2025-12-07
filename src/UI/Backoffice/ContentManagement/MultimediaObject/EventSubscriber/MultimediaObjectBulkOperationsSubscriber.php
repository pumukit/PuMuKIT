<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\EventSubscriber;

use App\UI\Backoffice\ContentManagement\MultimediaObject\Event\MultimediaObjectBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class MultimediaObjectBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MultimediaObjectBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(MultimediaObjectBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'multimedia_object_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected multimedia objects? This action cannot be undone.',
            priority: 100
        );
    }
}
