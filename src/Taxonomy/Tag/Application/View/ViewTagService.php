<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\View;

use App\Taxonomy\Tag\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;
use Pumukit\SchemaBundle\Document\Tag;

final class ViewTagService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository
    ) {}

    public function __invoke(ViewTagRequest $request): ViewTagResponse
    {
        ViewTagValidator::validate($request);

        $tag = $this->tagRepository->find($request->id);
        if (!$tag instanceof Tag) {
            throw TagNotFoundException::withId($request->id);
        }

        return new ViewTagResponse($tag);
    }
}
