<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Controller;

use App\Taxonomy\Tag\Application\List\ListTagsRequest;
use App\Taxonomy\Tag\Application\List\ListTagsService;
use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;
use App\Taxonomy\Tag\Infrastructure\Ui\Backoffice\Http\Presenter\TagDataTablePresenter;
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
        $lazyLoad = $request->query->getBoolean('lazy', true);
        $onlyRoots = $request->query->getBoolean('only_roots', false);
        $parentId = $request->query->get('parent_id');

        $allTags = $this->tagRepository->findAll();
        $rootTag = null;
        foreach ($allTags as $tag) {
            if (null === $tag->getParent()) {
                $rootTag = $tag;

                break;
            }
        }

        $rootParentId = $rootTag ? $rootTag->getId() : null;

        if ($lazyLoad) {
            $tags = [];
            if ($rootTag) {
                $tags = $this->tagRepository->findChildren($rootTag);
            }
        } else {
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
