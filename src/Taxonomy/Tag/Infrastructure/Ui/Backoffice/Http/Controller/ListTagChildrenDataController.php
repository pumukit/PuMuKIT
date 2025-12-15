<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;
use App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Presenter\TagDataTablePresenter;
use Pumukit\SchemaBundle\Document\Tag;
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
        $parentTag = $this->tagRepository->find($parentId);

        if (!$parentTag instanceof Tag) {
            return new JsonResponse(['error' => 'Parent tag not found'], 404);
        }

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
