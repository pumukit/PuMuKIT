<?php

declare(strict_types=1);

namespace App\Transcoding\UI\Backoffice\EventSubscriber;

use App\Shared\UI\Backoffice\Menu\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TranscodingMenuSubscriber implements EventSubscriberInterface
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
            key: 'transcoding_jobs',
            label: 'Jobs',
            route: 'transcoding_jobs_list',
            parent: 'transcoding',
            icon: 'fa-cogs',
            priority: 100,
            permission: Permission::ACCESS_JOBS
        );

        $event->addItem(
            key: 'transcoding_cpus',
            label: 'CPUs',
            route: 'transcoding_cpus_list',
            parent: 'transcoding',
            icon: 'fa-microchip',
            priority: 200,
            permission: Permission::ACCESS_JOBS
        );
    }
}

