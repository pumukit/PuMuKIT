<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\EventSubscriber;

use App\UI\Backoffice\Shared\Menu\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PersonMenuSubscriber implements EventSubscriberInterface
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
            key: 'person',
            label: 'People',
            route: 'person_list',
            parent: 'management',
            icon: 'fa-people-group',
            priority: 100,
        );
    }
}
