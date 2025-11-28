<?php

declare(strict_types=1);

namespace App\Series\UI\Backoffice\EventSubscriber;

use App\Series\UI\Backoffice\Event\SeriesViewTabsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class SeriesViewTabsSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            SeriesViewTabsEvent::NAME => 'onViewTabs',
        ];
    }

    public function onViewTabs(SeriesViewTabsEvent $event): void
    {
        $event->addTab(
            key: 'general',
            label: 'General',
            icon: 'fa-solid fa-ellipsis',
            template: '@Series/UI/Backoffice/Pages/tabs/general.html.twig',
            priority: 10
        );

        $event->addTab(
            key: 'objects',
            label: 'Multimedia Objects',
            icon: 'fa-solid fa-photo-film',
            template: '@Series/UI/Backoffice/Pages/tabs/objects.html.twig',
            priority: 20
        );

        $event->addTab(
            key: 'events',
            label: 'Events',
            icon: 'fa-solid fa-circle',
            template: '@Series/UI/Backoffice/Pages/tabs/events.html.twig',
            priority: 30
        );

        $event->addTab(
            key: 'edit',
            label: 'Series',
            icon: 'fa-solid fa-edit',
            template: '@Series/UI/Backoffice/Pages/tabs/edit.html.twig',
            priority: 40
        );

        $event->addTab(
            key: 'template',
            label: 'Template',
            icon: 'fa-solid fa-file-lines',
            template: '@Series/UI/Backoffice/Pages/tabs/template.html.twig',
            priority: 50
        );
    }
}
