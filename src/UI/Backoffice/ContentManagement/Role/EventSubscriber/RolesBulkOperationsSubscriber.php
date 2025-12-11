<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\EventSubscriber;

use App\UI\Backoffice\ContentManagement\Role\Event\RoleBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RolesBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RoleBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(RoleBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'role_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'Are you sure you want to delete the selected roles? This action cannot be undone.',
            priority: 100
        );
    }
}

