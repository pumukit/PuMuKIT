<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\List;

use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;

final class ListTagsService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(ListTagsRequest $request): ListTagsResponse
    {
        ListTagsValidator::validate($request);

        $tags = $this->tagRepository->findAll();

        return new ListTagsResponse($tags);
    }
}
