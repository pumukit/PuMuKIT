<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\ViewSeries\ViewSeriesHandler;
use App\Series\Application\ViewSeries\ViewSeriesQuery;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;

final class ViewSeriesController extends AbstractController
{
    public function __construct(private ViewSeriesHandler $handler, private RouterInterface $router) {}

    public function __invoke(Request $request, string $id, string $tab = 'objects'): Response
    {
        $response = $this->handler->handle(new ViewSeriesQuery($id, $tab));

        $multimediaObjects = array_map(function ($item) {
            $item['actions'] = [
                [
                    'url' => $this->router->generate('multimediaobject_view', ['id' => $item['id']]),
                    'icon' => 'fa fa-eye',
                    'label' => 'View',
                ],
                [
                    'url' => '#',
                    'icon' => 'fa fa-trash',
                    'label' => 'Delete',
                ],
            ];

            return $item;
        }, $response->multimediaObjects());

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $response->series(),
            'multimediaObjects' => $multimediaObjects,
            'tab' => $response->tab(),
        ]);
    }
}
