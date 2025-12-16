<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\IdentityAndAccess\Group\Infrastructure\Security\Permission\GroupUIPermissions;
use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class GroupMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MenuBuildEvent::NAME => 'onMenuBuild',
        ];
    }

    public function onMenuBuild(MenuBuildEvent $event): void
    {
        $event->addItem(
            key: 'group',
            label: 'Groups',
            route: 'group_list',
            parent: 'management',
            icon: 'fa-users',
            priority: 90,
            permission: GroupUIPermissions::getMenuPermission(),
            activeRoutes: ['group_create', 'group_view', 'group_update', 'group_delete', 'groups_list_data']
        );
    }
}
