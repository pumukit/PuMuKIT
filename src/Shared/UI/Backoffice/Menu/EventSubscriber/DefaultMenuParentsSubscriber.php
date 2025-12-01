<?php

declare(strict_types=1);

namespace App\Shared\UI\Backoffice\Menu\EventSubscriber;

use App\Shared\UI\Backoffice\Menu\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DefaultMenuParentsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            MenuBuildEvent::NAME => ['onMenuBuild', 1000],
        ];
    }

    public function onMenuBuild(MenuBuildEvent $event): void
    {
        $event->addParent(
            key: 'dashboard',
            label: 'Dashboard',
            icon: 'fa-home',
            priority: 1000,
            permission: null
        );

        $event->addParent(
            key: 'media_manager',
            label: 'Media Manager',
            icon: 'fa-photo-video',
            priority: 900,
            permission: null
        );

        $event->addParent(
            key: 'live',
            label: 'Live',
            icon: 'fa-broadcast-tower',
            priority: 800,
            permission: null
        );

        $event->addParent(
            key: 'tables',
            label: 'Tables',
            icon: 'fa-table',
            priority: 700,
            permission: null
        );

        $event->addParent(
            key: 'management',
            label: 'Management',
            icon: 'fa-cogs',
            priority: 600,
            permission: null
        );

        $event->addParent(
            key: 'jobs',
            label: 'Jobs',
            icon: 'fa-tasks',
            priority: 500,
            permission: null
        );

        $event->addParent(
            key: 'statistics',
            label: 'Statistics',
            icon: 'fa-chart-bar',
            priority: 400,
            permission: null
        );

        $event->addParent(
            key: 'system',
            label: 'System',
            icon: 'fa-cog',
            priority: 300,
            permission: null
        );
    }
}

