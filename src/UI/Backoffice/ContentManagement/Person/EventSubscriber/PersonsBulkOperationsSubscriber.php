<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\EventSubscriber;

use App\UI\Backoffice\ContentManagement\Person\Event\PersonBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PersonsBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            PersonBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(PersonBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'person_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected persons? This action cannot be undone.',
            priority: 100
        );
    }
}
