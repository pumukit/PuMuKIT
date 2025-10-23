<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ListSeries\ListSeriesHandler;
use App\Series\Application\ListSeries\ListSeriesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

final class ListSeriesController extends AbstractController
{
    public function __construct(private ListSeriesHandler $handler) {}

    public function __invoke(): Response
    {
        $response = $this->handler->handle(new ListSeriesQuery());

        return $this->render('@Series/UI/Backend/Pages/list.html.twig', [
            'series' => $response->series(),
        ]);
    }
}
