<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\DeleteTag;

use App\Taxonomy\Domain\Event\TagDeleted;
use App\Taxonomy\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Domain\Repository\TagRepositoryInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final readonly class DeleteTagService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private EventDispatcherInterface $eventDispatcher
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
        $this->eventDispatcher->dispatch($event);

        $this->tagRepository->delete($tag);
    }
}

