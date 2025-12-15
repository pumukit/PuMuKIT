<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Menu\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Menu\Event\MenuBuildEvent;
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
            key: 'home',
            label: 'Home',
            icon: 'fa-home',
            priority: 1,
            permission: null
        );

        $event->addParent(
            key: 'media_manager',
            label: 'Content',
            icon: 'fa-photo-video',
            priority: 100,
            permission: null
        );

        $event->addParent(
            key: 'media_processing',
            label: 'Media Processing',
            icon: 'fa-tasks',
            priority: 200,
            permission: null
        );

        $event->addParent(
            key: 'streaming',
            label: 'Streaming',
            icon: 'fa-circle',
            priority: 300,
            permission: null
        );

        $event->addParent(
            key: 'management',
            label: 'Administration',
            icon: 'fa-cogs',
            priority: 400,
            permission: null
        );

        $event->addParent(
            key: 'statistics',
            label: 'Statistics',
            icon: 'fa-chart-bar',
            priority: 500,
            permission: null
        );

        $event->addParent(
            key: 'connected_app',
            label: 'Applications',
            icon: 'fa-plug',
            priority: 600,
            permission: null
        );

        $event->addParent(
            key: 'system',
            label: 'System',
            icon: 'fa-cog',
            priority: 1000,
            permission: null
        );
    }
}
