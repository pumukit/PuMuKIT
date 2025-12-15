<?php

declare(strict_types=1);

namespace App\ContentManagement\MultimediaObject\Infrastructure\Ui\Backoffice\Http\Controller;

use App\ContentManagement\MultimediaObject\Application\View\ViewMultimediaObjectRequest;
use App\ContentManagement\MultimediaObject\Application\View\ViewMultimediaObjectService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ViewMultimediaObjectController extends AbstractController
{
    public function __construct(private ViewMultimediaObjectService $viewMultimediaObjectService) {}

    public function __invoke(Request $request, string $id, string $tab = 'general'): Response
    {
        $dto = new ViewMultimediaObjectRequest($id, $tab);
        $response = ($this->viewMultimediaObjectService)($dto);

        return $this->render('@MultimediaObject/Views/view.html.twig', [
            'object' => $response->multimediaObject,
            'tab' => $response->tab,
        ]);
    }
}
