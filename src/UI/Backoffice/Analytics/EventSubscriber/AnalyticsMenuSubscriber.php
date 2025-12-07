<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Analytics\EventSubscriber;

use App\UI\Backoffice\Shared\Menu\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class AnalyticsMenuSubscriber implements EventSubscriberInterface
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
            key: 'analytics',
            label: 'Stats',
            route: 'analytics_index',
            parent: 'home',
            icon: 'fa-chart-bar',
            priority: 200,
        );
    }
}
