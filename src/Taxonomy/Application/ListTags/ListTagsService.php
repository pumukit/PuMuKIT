<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\ListTags;

use App\Taxonomy\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Domain\Repository\TagRepositoryInterface;

final readonly class ListTagsService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(ListTagsRequest $request): ListTagsResponse
    {
        //        if ($request->onlyRoots) {
        //            $tags = $this->tagRepository->findRoots();
        //        } elseif ($request->parentId) {
        //            $parent = $this->tagRepository->find($request->parentId);
        //            if (!$parent) {
        //                throw TagNotFoundException::withId($request->parentId);
        //            }
        //            $tags = $this->tagRepository->findChildren($parent);
        //        } else {
        $tags = $this->tagRepository->findAll();
        //        }

        return new ListTagsResponse($tags);
    }
}
