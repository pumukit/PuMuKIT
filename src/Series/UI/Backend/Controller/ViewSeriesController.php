<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\View\ViewSeriesRequest;
use App\Series\Application\View\ViewSeriesService;
use App\Series\Domain\Event\SeriesFormBuildEvent;
use App\Series\Domain\Event\SeriesViewTabsEvent;
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

        $allTabKeys = array_map(fn($t) => $t['key'], $viewTabsEvent->getTabs());

        if (!in_array($tab, $allTabKeys)) {
            return $this->redirectToRoute('series_view', [
                'id' => $id,
                'tab' => 'general'
            ]);
        }

        $formBuildEvent = null;
        if ('edit' === $tab) {
            $formBuildEvent = new SeriesFormBuildEvent($seriesResponse->series);
            $this->eventDispatcher->dispatch($formBuildEvent, SeriesFormBuildEvent::NAME);
        }

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $seriesResponse->series,
            'tab' => $tab,
            'formBuildEvent' => $formBuildEvent,
            'viewTabsEvent' => $viewTabsEvent,
            'customTabs' => $viewTabsEvent->getTabs(),
        ]);
    }
}
