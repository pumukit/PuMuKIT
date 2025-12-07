<?php

namespace App\UI\Backoffice\Dashboard\EventSubscriber;

use App\UI\Backoffice\Shared\Menu\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DashboardMenuSubscriber implements EventSubscriberInterface
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
            key: 'dashboard',
            label: 'Dashboard',
            route: 'dashboard',
            parent: 'home',
            icon: 'fa-chart-line',
            priority: 100,
            permission: Permission::ACCESS_DASHBOARD
        );
    }
}
