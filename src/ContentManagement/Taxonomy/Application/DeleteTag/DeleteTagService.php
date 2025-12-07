<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\DeleteTag;

use App\ContentManagement\Taxonomy\Domain\Event\TagDeleted;
use App\ContentManagement\Taxonomy\Domain\Exception\TagNotFoundException;
use App\ContentManagement\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final readonly class DeleteTagService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(DeleteTagRequest $request): void
    {
        $tag = $this->tagRepository->find($request->id);
        if (!$tag) {
            throw TagNotFoundException::withId($request->id);
        }

        // Lanzar evento de dominio antes de eliminar
        $event = TagDeleted::fromTag(
            $tag->getId(),
            $tag->getCod()
        );
        $this->eventBus->dispatch($event);

        $this->tagRepository->delete($tag);
    }
}
