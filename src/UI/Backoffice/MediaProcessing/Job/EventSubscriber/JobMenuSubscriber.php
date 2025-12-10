<?php

declare(strict_types=1);

namespace App\UI\Backoffice\MediaProcessing\Job\EventSubscriber;

use App\UI\Backoffice\Shared\Menu\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class JobMenuSubscriber implements EventSubscriberInterface
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
            key: 'media_processing_jobs',
            label: 'Jobs',
            route: 'media_processing_jobs_list',
            parent: 'media_processing',
            icon: 'fa-cogs',
            priority: 100,
            permission: Permission::ACCESS_JOBS
        );
    }
}
