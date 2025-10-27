<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\View\ViewSeriesService;
use App\Series\Application\View\ViewSeriesRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewSeriesController extends AbstractController
{
    public function __construct(private ViewSeriesService $viewSeriesService) {}

    public function __invoke(Request $request, string $id, string $tab = 'objects'): Response
    {
        $dto = new ViewSeriesRequest($id);
        $seriesResponse = ($this->viewSeriesService)($dto);

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $seriesResponse->series,
            'tab' => $tab,
        ]);
    }
}
