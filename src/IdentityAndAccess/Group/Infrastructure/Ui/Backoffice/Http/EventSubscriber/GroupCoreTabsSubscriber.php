<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\EventSubscriber;

use App\IdentityAndAccess\Group\Application\Update\UpdateGroupRequest;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event\GroupViewTabsEvent;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Form\GroupUpdateType;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormFactoryInterface;

final class GroupCoreTabsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly FormFactoryInterface $formFactory
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            GroupViewTabsEvent::NAME => ['onGroupViewTabs', 100],
        ];
    }

    public function onGroupViewTabs(GroupViewTabsEvent $event): void
    {
        $group = $event->getGroup();
        $activeTab = $event->getActiveTab();

        $event->addTab(
            'general',
            'group.view.tabs.general',
            'fa-info-circle',
            0,
            '@Group/Views/tabs/general.html.twig'
        );

        $editParams = [];

        if ('edit' === $activeTab) {
            $updateRequest = UpdateGroupRequest::fromGroup($group);
            $form = $this->formFactory->create(GroupUpdateType::class, $updateRequest);
            $editParams['form'] = $form->createView();
        }

        $event->addTab(
            'edit',
            'group.view.tabs.edit',
            'fa-edit',
            10,
            '@Group/Views/tabs/edit.html.twig',
            $editParams
        );
    }
}
