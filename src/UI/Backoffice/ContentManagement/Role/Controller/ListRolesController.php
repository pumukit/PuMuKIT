<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Role\Controller;

use App\UI\Backoffice\ContentManagement\Role\Event\RoleBulkOperationsEvent;
use App\UI\Backoffice\ContentManagement\Role\Event\RoleListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

final class ListRolesController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(): Response
    {
        $bulkOperationsEvent = new RoleBulkOperationsEvent();
        $this->eventDispatcher->dispatch($bulkOperationsEvent, RoleBulkOperationsEvent::NAME);

        $operations = $bulkOperationsEvent->getOperations();
        foreach ($operations as &$operation) {
            if ('route' === $operation['type']) {
                if ('#' === $operation['handler']) {
                    continue;
                }
                $operation['handler'] = $this->generateUrl(
                    $operation['handler'],
                    $operation['route_params'] ?? []
                );
                $operation['type'] = 'url';
            }
        }

        $listActionsEvent = new RoleListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, RoleListActionsEvent::NAME);

        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@Role/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
