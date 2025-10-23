<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeries\ViewSeriesHandler;
use App\Series\Application\ViewSeries\ViewSeriesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ViewSeriesController extends AbstractController
{
    public function __construct(private ViewSeriesHandler $handler) {}

    public function __invoke(string $id): Response
    {
        $response = $this->handler->handle(new ViewSeriesQuery($id));

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $response->series(),
            'multimediaObjects' => $response->multimediaObjects(),
        ]);
    }
}
