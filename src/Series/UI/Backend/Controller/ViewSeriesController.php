<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\View\ViewSeriesHandler;
use App\Series\Application\View\ViewSeriesRequest;
use App\Series\Domain\SeriesRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewSeriesController extends AbstractController
{
    public function __invoke(Request $request, SeriesRepositoryInterface $repository, string $id, string $tab = 'objects'): Response
    {
        $dto = new ViewSeriesRequest($id);
        $handler = new ViewSeriesHandler($repository);
        $seriesResponse = $handler->execute($dto);

        return $this->render('@Series/UI/Backend/Pages/view.html.twig', [
            'series' => $seriesResponse->series,
            'tab' => $tab,
        ]);
    }
}
