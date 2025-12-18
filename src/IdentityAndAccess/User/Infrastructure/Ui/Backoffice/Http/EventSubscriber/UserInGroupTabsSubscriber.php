<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\User\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event\GroupViewTabsEvent;
use App\IdentityAndAccess\User\Domain\Repository\UserRepositoryInterface;
use App\Shared\Domain\Criteria\Criteria;
use App\Shared\Domain\Criteria\Filter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class UserInGroupTabsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly UserRepositoryInterface $repository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            GroupViewTabsEvent::NAME => ['onGroupViewTabs', 20],
        ];
    }

    public function onGroupViewTabs(GroupViewTabsEvent $event): void
    {
        $group = $event->getGroup();
        $activeTab = $event->getActiveTab();

        $parameters = [];

        if ('users' === $activeTab) {
            $criteria = new Criteria([
                new Filter('groups', '=', $group->getId()),
            ]);

            $parameters = [
                'total' => $this->repository->totalMatching($criteria),
                'data_url' => $this->urlGenerator->generate('users_group_data', ['id' => $group->getId()]),
            ];
        }

        $event->addTab(
            'users',
            'group.view.tabs.users',
            'fa-user',
            30,
            '@User/Views/tabs/users_in_group.html.twig',
            $parameters
        );
    }
}
