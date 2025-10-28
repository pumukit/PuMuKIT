<?php

declare(strict_types=1);

namespace App\MultimediaObject\UI\Backend\Controller;

use App\MultimediaObject\Application\Delete\DeleteMultimediaObjectRequest;
use App\MultimediaObject\Application\Delete\DeleteMultimediaObjectService;
use App\MultimediaObject\Domain\MultimediaObjectRepositoryInterface;
use App\Series\Domain\SeriesRepositoryInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeleteMultimediaObjectController extends AbstractController
{
    public function __invoke(string $id, DeleteMultimediaObjectService $deleteMultimediaObjectService): RedirectResponse
    {
        $requestDto = new DeleteMultimediaObjectRequest($id);

        $response = ($deleteMultimediaObjectService)($requestDto);

        if ($response->success) {
            $this->addFlash('success', $response->message);
        } else {
            $this->addFlash('danger', $response->message);
        }

        if($response->series) {
            return $this->redirectToRoute('series_view', ['id' => $response->series]);
        }

        return $this->redirectToRoute('series_list');
    }
}

