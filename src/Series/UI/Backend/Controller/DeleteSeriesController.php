<?php

namespace App\Series\UI\Backend\Controller;

use App\Series\Application\Delete\DeleteSeriesHandler;
use App\Series\Application\Delete\DeleteSeriesRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteSeriesController extends AbstractController
{
    public function __invoke(string $id, DeleteSeriesHandler $handler): RedirectResponse
    {
        $requestDto = new DeleteSeriesRequest($id);

        $response = $handler($requestDto);

        if ($response->success) {
            $this->addFlash('success', $response->message);
        } else {
            $this->addFlash('danger', $response->message);
        }

        return $this->redirectToRoute('series_list');
    }
}
