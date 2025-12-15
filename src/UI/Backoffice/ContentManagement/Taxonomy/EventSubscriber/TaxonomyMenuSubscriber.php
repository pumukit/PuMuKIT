<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Taxonomy\EventSubscriber;

use App\Shared\Infrastructure\Ui\Backoffice\Http\Event\MenuBuildEvent;
use Pumukit\SchemaBundle\Security\Permission;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TaxonomyMenuSubscriber implements EventSubscriberInterface
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
            key: 'tags',
            label: 'Tags',
            route: 'taxonomy_tag_index',
            parent: 'management',
            icon: 'fa-tags',
            priority: 100,
            permission: Permission::ACCESS_TAGS
        );
    }
}
