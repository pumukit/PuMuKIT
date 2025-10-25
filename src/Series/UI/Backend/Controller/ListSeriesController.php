<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ListSeries\ListSeriesHandler;
use App\Series\Application\ListSeries\ListSeriesRequest;
use App\Series\Domain\SeriesRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class ListSeriesController extends AbstractController
{
    public function __invoke(Request $request, SeriesRepositoryInterface $repository, RouterInterface $router): Response
    {
        $dto = new ListSeriesRequest(
            (int) $request->query->get('page', 1),
            (int) $request->query->get('limit', 20),
            [],
        );

        $handler = new ListSeriesHandler($repository);
        $seriesResponse = $handler->execute($dto);

        $seriesWithActions = [];
        foreach ($seriesResponse->series as $item) {
            $row = [
                'oneSeries'   => $item,
                'objectCount' => $repository->countMultimediaObjects($item->getId()),
                'eventCount'  => $repository->countEventMultimediaObjects($item->getId()),
                'actions'     => [
                    'view'   => $router->generate('series_view', ['id' => $item->getId()]),
                    'delete' => '#',
                ],
            ];
            $seriesWithActions[] = $row;
        }

        return $this->render('@Series/UI/Backend/Pages/list.html.twig', [
            'series' => $seriesWithActions,
        ]);
    }
}
