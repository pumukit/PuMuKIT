<?php

declare(strict_types=1);

namespace App\Taxonomy\Application\UpdateTag;

use App\Taxonomy\Domain\Event\TagUpdated;
use App\Taxonomy\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final readonly class UpdateTagService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdateTagRequest $request): UpdateTagResponse
    {
        $tag = $this->tagRepository->find($request->id);
        if (!$tag) {
            throw TagNotFoundException::withId($request->id);
        }

        // Actualizar usando métodos de la entidad Legacy
        $tag->setI18nTitle($request->title);
        $tag->setI18nDescription($request->description);

        if ($request->slug) {
            $tag->setSlug($request->slug);
        }

        $tag->setMetatag($request->metatag);
        $tag->setDisplay($request->display);

        if ($request->properties !== null) {
            $tag->setProperties($request->properties);
        }

        $this->tagRepository->save($tag);

        // Lanzar evento de dominio
        $event = TagUpdated::fromTag(
            $tag->getId(),
            $tag->getCod(),
            $tag->getI18nTitle()
        );
        $this->eventBus->dispatch($event);

        return new UpdateTagResponse($tag);
    }
}

