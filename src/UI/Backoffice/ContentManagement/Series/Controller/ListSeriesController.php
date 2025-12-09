<?php

namespace App\UI\Backoffice\ContentManagement\Series\Controller;

use App\UI\Backoffice\ContentManagement\Series\Event\SeriesBulkOperationsEvent;
use App\UI\Backoffice\ContentManagement\Series\Event\SeriesListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Response;

final class ListSeriesController extends AbstractController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(): Response
    {
        $bulkOperationsEvent = new SeriesBulkOperationsEvent();
        $this->eventDispatcher->dispatch($bulkOperationsEvent, SeriesBulkOperationsEvent::NAME);

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

        $listActionsEvent = new SeriesListActionsEvent();
        $this->eventDispatcher->dispatch($listActionsEvent, SeriesListActionsEvent::NAME);

        $customActions = $listActionsEvent->getActions();
        foreach ($customActions as &$action) {
            if ('route' === $action['type']) {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@Series/Views/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
