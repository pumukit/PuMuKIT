<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Group\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Group\Application\View\ViewGroupRequest;
use App\IdentityAndAccess\Group\Application\View\ViewGroupService;
use App\IdentityAndAccess\Group\Domain\Query\MultimediaObjectQueryInterface;
use App\IdentityAndAccess\Group\Domain\ValueObject\GroupId;
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
        private MultimediaObjectQueryInterface $multimediaObjectQuery
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $dto = new ViewGroupRequest($id, $tab);
        $response = ($this->viewGroupService)($dto);

        $tabsEvent = new GroupViewTabsEvent($response->group);
        $this->eventDispatcher->dispatch($tabsEvent, GroupViewTabsEvent::NAME);

        $tabData = [];
        if ('multimedia_objects' === $tab) {
            $total = $this->multimediaObjectQuery->countByGroupId(GroupId::fromString($id));

            $tabData = [
                'total' => $total,
                'data_url' => $this->generateUrl('group_multimedia_objects_data', ['id' => $id]),
            ];
        }

        return $this->render('@Group/Views/view.html.twig', [
            'group' => $response->group,
            'tab' => $response->tab,
            'tabData' => $tabData,
            'tabs' => $tabsEvent->getTabs(),
        ]);
    }
}
