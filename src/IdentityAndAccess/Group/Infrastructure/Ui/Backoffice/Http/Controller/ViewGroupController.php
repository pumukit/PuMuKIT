<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Application\View\ViewGroupRequest;
use App\IdentityAndAccess\Group\Application\View\ViewGroupService;
use App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Event\GroupViewTabsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewGroupController extends AbstractController
{
    public function __construct(
        private ViewGroupService $viewGroupService,
        private EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $dto = new ViewGroupRequest($id, $tab);
        $response = ($this->viewGroupService)($dto);

        $tabsEvent = new GroupViewTabsEvent($response->group, $tab);
        $this->eventDispatcher->dispatch($tabsEvent, GroupViewTabsEvent::NAME);

        $tabs = $tabsEvent->getTabs();

        if (!array_key_exists($tab, $tabs)) {
            return $this->redirectToRoute('group_view', ['id' => $id, 'tab' => 'general']);
        }

        $activeTab = $tabs[$tab];

        return $this->render('@Group/Views/view.html.twig', [
            'group' => $response->group,
            'tabs' => $tabs,
            'activeTab' => $activeTab,
        ]);
    }
}
