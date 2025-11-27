<?php

namespace App\Series\UI\Backend\Controller;

use App\MultimediaObject\UI\Backend\Event\MultimediaObjectBulkOperationsEvent;
use App\MultimediaObject\UI\Backend\Event\MultimediaObjectListActionsEvent;
use App\Series\Application\View\ViewSeriesRequest;
use App\Series\Application\View\ViewSeriesService;
use App\Series\UI\Backend\Event\SeriesFormBuildEvent;
use App\Series\UI\Backend\Event\SeriesViewTabsEvent;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ViewSeriesController extends AbstractController
{
    public function __construct(
        private ViewSeriesService $viewSeriesService,
        private EventDispatcherInterface $eventDispatcher
    ) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $dto = new ViewSeriesRequest($id, $tab);
        $seriesResponse = ($this->viewSeriesService)($dto);

        $viewTabsEvent = new SeriesViewTabsEvent($seriesResponse->series);
        $this->eventDispatcher->dispatch($viewTabsEvent, SeriesViewTabsEvent::NAME);

        $allTabKeys = array_map(fn ($t) => $t['key'], $viewTabsEvent->getTabs());

        if (!in_array($tab, $allTabKeys)) {
            return $this->redirectToRoute('series_view', [
                'id' => $id,
                'tab' => 'general',
            ]);
        }

        $formBuildEvent = null;
        if ('edit' === $tab) {
            $formBuildEvent = new SeriesFormBuildEvent($seriesResponse->series);
            $this->eventDispatcher->dispatch($formBuildEvent, SeriesFormBuildEvent::NAME);
        }

        // Dispatch event for multimedia object bulk operations (objects tab)
        $multimediaObjectBulkOperationsEvent = null;
        $multimediaObjectBulkOperations = [];
        $multimediaObjectListActionsEvent = null;
        $multimediaObjectCustomActions = [];

        if ('objects' === $tab) {
            // Bulk operations
            $multimediaObjectBulkOperationsEvent = new MultimediaObjectBulkOperationsEvent();
            $this->eventDispatcher->dispatch($multimediaObjectBulkOperationsEvent, MultimediaObjectBulkOperationsEvent::NAME);

            // Convert route names to URLs for bulk operations
            $multimediaObjectBulkOperations = $multimediaObjectBulkOperationsEvent->getOperations();
            foreach ($multimediaObjectBulkOperations as &$operation) {
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

            // Individual row actions
            $multimediaObjectListActionsEvent = new MultimediaObjectListActionsEvent();
            $this->eventDispatcher->dispatch($multimediaObjectListActionsEvent, MultimediaObjectListActionsEvent::NAME);

            // Convert route names to URLs for individual actions
            $multimediaObjectCustomActions = $multimediaObjectListActionsEvent->getActions();
            foreach ($multimediaObjectCustomActions as &$action) {
                if ('route' === $action['type']) {
                    if ('#' === $action['url']) {
                        continue;
                    }
                    $action['url'] = $this->generateUrl(
                        $action['url'],
                        $action['route_params'] ?? []
                    );
                    $action['type'] = 'url';
                }
            }
        }

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $seriesResponse->series,
            'tab' => $tab,
            'formBuildEvent' => $formBuildEvent,
            'viewTabsEvent' => $viewTabsEvent,
            'customTabs' => $viewTabsEvent->getTabs(),
            'multimediaObjectBulkOperationsEvent' => $multimediaObjectBulkOperationsEvent,
            'multimediaObjectBulkOperations' => $multimediaObjectBulkOperations,
            'multimediaObjectListActionsEvent' => $multimediaObjectListActionsEvent,
            'multimediaObjectCustomActions' => $multimediaObjectCustomActions,
        ]);
    }
}
