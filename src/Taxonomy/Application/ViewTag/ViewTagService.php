<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\ViewTag;

use App\Taxonomy\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Domain\Repository\TagRepositoryInterface;

final readonly class ViewTagService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(ViewTagRequest $request): ViewTagResponse
    {
        $tag = $this->tagRepository->find($request->id);
        if (!$tag) {
            throw TagNotFoundException::withId($request->id);
        }

        return new ViewTagResponse($tag);
    }
}

