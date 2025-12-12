<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Person\Controller;

use App\UI\Backoffice\ContentManagement\Person\Event\PersonBulkOperationsEvent;
use App\UI\Backoffice\ContentManagement\Person\Event\PersonListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

final class ListPersonsController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(): Response
    {
        $bulkOperationsEvent = new PersonBulkOperationsEvent();
        $this->eventDispatcher->dispatch($bulkOperationsEvent, PersonBulkOperationsEvent::NAME);
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
        $listActionsEvent = new PersonListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, PersonListActionsEvent::NAME);
        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@Person/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
