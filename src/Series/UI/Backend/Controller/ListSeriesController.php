<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ListSeries\ListSeriesHandler;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ListSeriesController extends AbstractController
{
    public function __construct(private ListSeriesHandler $handler) {}

    public function __invoke(Request $request): Response
    {
        $filters = [
            'title' => $request->query->get('title'),
        ];

        $response = $this->handler->handle($filters);

        return $this->render('@Series/UI/Backend/Pages/list.html.twig', [
            'series' => $response->series(),
        ]);
    }
}
