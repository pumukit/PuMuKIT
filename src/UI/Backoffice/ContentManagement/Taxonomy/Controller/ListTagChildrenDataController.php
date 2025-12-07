<?php

declare(strict_types=1);

namespace App\UI\Backoffice\ContentManagement\Taxonomy\Controller;

use App\ContentManagement\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\UI\Backoffice\ContentManagement\Taxonomy\Presenter\TagDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

final class ListTagChildrenDataController extends AbstractController
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private TagDataTablePresenter $presenter
    ) {}

    public function __invoke(string $parentId): JsonResponse
    {
        // Buscar el tag padre
        $parentTag = $this->tagRepository->find($parentId);

        if (!$parentTag) {
            return new JsonResponse(['error' => 'Parent tag not found'], 404);
        }

        // Obtener hijos directos
        $children = $this->tagRepository->findChildren($parentTag);

        $data = array_map(
            fn ($tag) => $this->presenter->present($tag),
            $children
        );

        return new JsonResponse([
            'data' => $data,
            'total' => count($data),
        ]);
    }
}
