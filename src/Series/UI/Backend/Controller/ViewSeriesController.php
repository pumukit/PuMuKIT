<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeries\ViewSeriesHandler;
use App\Series\Application\ViewSeries\ViewSeriesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewSeriesController extends AbstractController
{
    public function __construct(private ViewSeriesHandler $handler) {}

    public function __invoke(Request $request, string $id, string $tab = 'objects'): Response
    {
        $response = $this->handler->handle(new ViewSeriesQuery($id, $tab));

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $response->series(),
            'multimediaObjects' => $response->multimediaObjects(),
            'tab' => $response->tab(),
        ]);
    }
}
