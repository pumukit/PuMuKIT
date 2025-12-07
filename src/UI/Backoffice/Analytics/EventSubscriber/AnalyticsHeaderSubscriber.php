<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Analytics\EventSubscriber;

use App\UI\Backoffice\Shared\Header\Event\HeaderBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Example: Analytics module adding a custom header button.
 */
final class AnalyticsHeaderSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HeaderBuildEvent::NAME => 'onHeaderBuild',
        ];
    }

    public function onHeaderBuild(HeaderBuildEvent $event): void
    {
        //        $event->addItem(
        //            key: 'analytics_stats',
        //            type: 'button',
        //            icon: 'fa-solid fa-chart-line',
        //            route: 'analytics_index',
        //            badge: '5',
        //            badgeClass: 'bg-info',
        //            priority: 150,
        //            permission: Permission::ACCESS_DASHBOARD,
        //            class: 'btn btn-default'
        //        );
    }
}
