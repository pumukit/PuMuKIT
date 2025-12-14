<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\ViewTag;

use Pumukit\SchemaBundle\Document\Tag;
use App\ContentManagement\Taxonomy\Domain\Exception\TagNotFoundException;
use App\ContentManagement\Taxonomy\Domain\Repository\TagRepositoryInterface;

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
