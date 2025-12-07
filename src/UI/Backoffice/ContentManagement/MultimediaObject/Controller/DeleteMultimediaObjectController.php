<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\MultimediaObject\Controller;

use App\ContentManagement\MultimediaObject\Application\Delete\DeleteMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\Delete\DeleteMultimediaObjectService;
use App\Shared\Domain\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;

final class DeleteMultimediaObjectController extends AbstractController
{
    public function __invoke(string $id, DeleteMultimediaObjectService $deleteMultimediaObjectService, TranslatorInterface $translator): RedirectResponse
    {
        $requestDto = new DeleteMultimediaObjectRequest($id);

        $response = ($deleteMultimediaObjectService)($requestDto);

        if ($response->success) {
            $this->addFlash('success', $translator->trans($response->message, [], 'multimedia_object'));
        } else {
            $this->addFlash('danger', $translator->trans($response->message, [], 'multimedia_object'));
        }

        if ($response->series) {
            return $this->redirectToRoute('series_view', ['id' => $response->series]);
        }

        return $this->redirectToRoute('series_list');
    }
}
