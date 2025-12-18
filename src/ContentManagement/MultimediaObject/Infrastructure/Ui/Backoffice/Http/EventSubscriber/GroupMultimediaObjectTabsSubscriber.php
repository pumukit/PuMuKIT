<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\ContentManagement\MultimediaObject\Domain\Repository\MultimediaObjectRepositoryInterface;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event\GroupViewTabsEvent;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class GroupMultimediaObjectTabsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MultimediaObjectRepositoryInterface $repository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            GroupViewTabsEvent::NAME => ['onGroupViewTabs', -10],
        ];
    }

    public function onGroupViewTabs(GroupViewTabsEvent $event): void
    {
        $group = $event->getGroup();
        $activeTab = $event->getActiveTab();

        $parameters = [];

        if ('multimedia_objects' === $activeTab) {
            $criteria = new Criteria([
                new Filter('groups', '=', $group->getId()),
            ]);

            $parameters = [
                'total' => $this->repository->totalMatching($criteria),
                'data_url' => $this->urlGenerator->generate('group_multimedia_objects_data', ['id' => $group->getId()]),
            ];
        }

        $event->addTab(
            'multimedia_objects',
            'group.view.tabs.multimedia_objects',
            'fa-photo-video',
            30,
            '@MultimediaObject/Views/tabs/multimedia_objects_in_group.html.twig',
            $parameters
        );
    }
}
