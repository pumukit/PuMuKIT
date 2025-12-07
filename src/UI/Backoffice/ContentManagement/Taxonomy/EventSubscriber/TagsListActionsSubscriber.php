<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Taxonomy\EventSubscriber;

use App\UI\Backoffice\ContentManagement\Taxonomy\Event\TagListActionsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TagsListActionsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            TagListActionsEvent::NAME => 'onListActions',
        ];
    }

    public function onListActions(TagListActionsEvent $event): void
    {
        $event->addAction(
            key: 'tags_create',
            label: 'Create',
            url: 'taxonomy_tag_create',
            type: 'route',
            icon: 'fa-plus',
            class: 'btn btn-pumukit',
            priority: 10
        );
    }
}
