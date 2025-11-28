<?php

namespace App\Series\UI\Backoffice\Controller;

use App\Series\Application\Delete\DeleteSeriesRequest;
use App\Series\Application\Delete\DeleteSeriesService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteSeriesController extends AbstractController
{
    public function __invoke(string $id, DeleteSeriesService $deleteSeriesService): RedirectResponse
    {
        $requestDto = new DeleteSeriesRequest($id);

        $response = ($deleteSeriesService)($requestDto);

        if ($response->success) {
            $this->addFlash('success', $response->message);
        } else {
            $this->addFlash('danger', $response->message);
        }

        return $this->redirectToRoute('series_list');
    }
}
