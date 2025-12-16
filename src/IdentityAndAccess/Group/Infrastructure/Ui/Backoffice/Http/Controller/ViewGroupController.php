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
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $dto = new ViewGroupRequest($id, $tab);
        $response = ($this->viewGroupService)($dto);

        // Dispatch event to allow other modules to add tabs
        $tabsEvent = new GroupViewTabsEvent($response->group);
        $this->eventDispatcher->dispatch($tabsEvent, GroupViewTabsEvent::NAME);

        return $this->render('@Group/Views/view.html.twig', [
            'group' => $response->group,
            'tab' => $response->tab,
            'tabs' => $tabsEvent->getTabs(),
        ]);
    }
}
