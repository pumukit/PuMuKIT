<?php

declare(strict_types=1);

namespace App\Taxonomy\UI\Backoffice\Controller;

use App\Taxonomy\Application\ListTags\ListTagsRequest;
use App\Taxonomy\Application\ListTags\ListTagsService;
use App\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\Taxonomy\UI\Backoffice\Presenter\TagDataTablePresenter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

final class ListTagsDataController extends AbstractController
{
    public function __construct(
        private ListTagsService $listTagsService,
        private TagDataTablePresenter $presenter,
        private TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(Request $request): JsonResponse
    {
        $lazyLoad = $request->query->getBoolean('lazy', true); // Por defecto activar lazy loading
        $onlyRoots = $request->query->getBoolean('only_roots', false);
        $parentId = $request->query->get('parent_id');

        // Encontrar el tag ROOT primero
        $allTags = $this->tagRepository->findAll();
        $rootTag = null;
        foreach ($allTags as $tag) {
            if (null === $tag->getParent()) {
                $rootTag = $tag;

                break;
            }
        }

        $rootParentId = $rootTag ? $rootTag->getId() : null;

        // Si lazy loading está activado, solo devolver tags de primer nivel
        if ($lazyLoad) {
            $tags = [];
            if ($rootTag) {
                // Solo hijos directos del ROOT
                $tags = $this->tagRepository->findChildren($rootTag);
            }
        } else {
            // Modo legacy: devolver todos los tags
            $listTagsRequest = new ListTagsRequest(
                parentId: $parentId,
                onlyRoots: $onlyRoots
            );
            $response = ($this->listTagsService)($listTagsRequest);
            $tags = $response->tags;
        }

        $data = array_map(
            fn ($tag) => $this->presenter->present($tag),
            $tags
        );

        return new JsonResponse([
            'data' => $data,
            'total' => count($data),
            'rootParentId' => $rootParentId,
            'lazyLoad' => $lazyLoad,
        ]);
    }
}
