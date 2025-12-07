<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Shared\Header\EventSubscriber;

use App\UI\Backoffice\Shared\Header\Event\HeaderBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class DefaultHeaderItemsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            HeaderBuildEvent::NAME => ['onHeaderBuild', 1000],
        ];
    }

    public function onHeaderBuild(HeaderBuildEvent $event): void
    {
        $event->addItem(
            key: 'notifications',
            type: 'button',
            icon: 'fa-solid fa-bell',
            badge: '99+',
            badgeClass: 'bg-danger',
            priority: 100,
            permission: null,
            class: 'btn btn-default'
        );
    }
}
