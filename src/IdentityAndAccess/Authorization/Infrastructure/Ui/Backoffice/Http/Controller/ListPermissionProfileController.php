<?php

declare(strict_types=1);

namespace App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Controller;

use App\IdentityAndAccess\Authorization\Infrastructure\Ui\Backoffice\Http\Event\PermissionProfileListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class ListPermissionProfileController extends AbstractController
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(): Response
    {
        $listActionsEvent = new PermissionProfileListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, PermissionProfileListActionsEvent::NAME);

        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@PermissionProfile/Views/list.html.twig', [
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
