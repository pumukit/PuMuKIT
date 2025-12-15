<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Delete;

use Pumukit\SchemaBundle\Document\Tag;
use App\Taxonomy\Tag\Domain\Event\TagDeletedEvent;
use App\Taxonomy\Tag\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class DeleteTagService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(DeleteTagRequest $request): void
    {
        DeleteTagValidator::validate($request);

        $tag = $this->tagRepository->find($request->id);
        if (!$tag instanceof Tag) {
            throw TagNotFoundException::withId($request->id);
        }

        $event = TagDeletedEvent::fromTag(
            $tag->getId(),
            $tag->getCod()
        );
        $this->eventBus->dispatch($event);

        $this->tagRepository->delete($tag);
    }
}
