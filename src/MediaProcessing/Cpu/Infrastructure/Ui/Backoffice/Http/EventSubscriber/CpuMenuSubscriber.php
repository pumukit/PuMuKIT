<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class CpuMenuSubscriber implements EventSubscriberInterface
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
            key: 'media_processing_cpus',
            label: 'CPUs',
            route: 'media_processing_cpus_list',
            parent: 'media_processing',
            icon: 'fa-microchip',
            priority: 200,
            permission: Permission::ACCESS_JOBS
        );
    }
}
