<?php

namespace App\User\UI\Backoffice\Controller;

use App\User\UI\Backoffice\Event\UserBulkOperationsEvent;
use App\User\UI\Backoffice\Event\UserListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

class ListUserController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(): Response
    {
        $bulkOperationsEvent = new UserBulkOperationsEvent();
        $this->eventDispatcher->dispatch($bulkOperationsEvent, UserBulkOperationsEvent::NAME);

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

        $listActionsEvent = new UserListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, UserListActionsEvent::NAME);

        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@User/UI/Backoffice/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
