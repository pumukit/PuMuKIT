<?php

declare(strict_types=1);

namespace App\ContentManagement\Role\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\ContentManagement\Role\Infrastructure\Ui\Backoffice\Http\Event\RoleListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class RolesListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            RoleListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(RoleListActionsEvent $event): void
    {
        $event->addAction(
            key: 'roles_create',
            label: 'Create',
            url: 'role_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
