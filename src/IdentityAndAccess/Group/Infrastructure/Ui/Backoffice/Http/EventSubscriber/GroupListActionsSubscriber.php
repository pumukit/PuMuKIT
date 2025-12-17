<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event\GroupListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GroupListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            GroupListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(GroupListActionsEvent $event): void
    {
        $event->addAction(
            key: 'group_create',
            label: 'Create',
            url: '#',
            type: 'modal',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10,
            routeParams: ['data-bs-toggle' => 'modal', 'data-bs-target' => '#createGroupModal']
        );
    }
}
