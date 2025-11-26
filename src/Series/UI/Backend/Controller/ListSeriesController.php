<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Domain\Event\SeriesBulkOperationsEvent;
use App\Series\Domain\Event\SeriesListActionsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\EventDispatcher\EventDispatcherInterface;

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
            if ($operation['type'] === 'route') {
                if($operation['handler'] === '#') {
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
            if ($action['type'] === 'route') {
                $action['url'] = $this->generateUrl(
                    $action['url'],
                    $action['route_params'] ?? []
                );
            }
        }

        return $this->render('@Series/UI/Backend/Pages/list.html.twig', [
            'bulkOperationsEvent' => $bulkOperationsEvent,
            'bulkOperations' => $operations,
            'listActionsEvent' => $listActionsEvent,
            'customActions' => $customActions,
        ]);
    }
}
