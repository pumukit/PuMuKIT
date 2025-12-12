<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ListTags;

use App\ContentManagement\Taxonomy\Domain\Repository\TagRepositoryInterface;

final class ListTagsService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(ListTagsRequest $request): ListTagsResponse
    {
        $tags = $this->tagRepository->findAll();

        return new ListTagsResponse($tags);
    }
}
