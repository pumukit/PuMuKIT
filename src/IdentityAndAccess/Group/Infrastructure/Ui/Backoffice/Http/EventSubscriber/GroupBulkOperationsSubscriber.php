<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event\GroupBulkOperationsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GroupBulkOperationsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            GroupBulkOperationsEvent::NAME => 'onBulkOperations',
        ];
    }

    public function onBulkOperations(GroupBulkOperationsEvent $event): void
    {
        $event->addOperation(
            key: 'delete',
            label: 'Delete',
            handler: 'group_bulk_delete',
            type: 'route',
            icon: 'fa-trash',
            confirmMessage: 'group.bulk_delete.confirm',
            priority: 100
        );
    }
}
