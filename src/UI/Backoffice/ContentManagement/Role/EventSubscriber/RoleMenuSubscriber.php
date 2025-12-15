<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Menu\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class RoleMenuSubscriber implements EventSubscriberInterface
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
            key: 'role',
            label: 'Roles',
            route: 'role_list',
            parent: 'management',
            icon: 'fa-users-viewfinder',
            priority: 100,
        );
    }
}
