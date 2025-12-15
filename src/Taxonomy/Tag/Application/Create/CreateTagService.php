<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Application\Create;

use App\Shared\Domain\EventBusInterface;
use App\Taxonomy\Tag\Domain\Event\TagCreatedEvent;
use App\Taxonomy\Tag\Domain\Exception\TagAlreadyExistsException;
use App\Taxonomy\Tag\Domain\Exception\TagNotFoundException;
use App\Taxonomy\Tag\Domain\Repository\TagRepositoryInterface;
use Pumukit\SchemaBundle\Document\Tag;

final class CreateTagService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateTagRequest $request): CreateTagResponse
    {
        CreateTagValidator::validate($request);

        $existingTag = $this->tagRepository->findByCod($request->cod);
        if (null !== $existingTag) {
            throw TagAlreadyExistsException::withCod($request->cod);
        }

        $parent = null;
        if ($request->parentId) {
            $parent = $this->tagRepository->find($request->parentId);
            if (!$parent instanceof Tag) {
                throw TagNotFoundException::withId($request->parentId);
            }
        }

        $tag = new Tag();
        $tag->setCod($request->cod);
        $tag->setI18nTitle($request->title);
        $tag->setI18nDescription($request->description);

        if ($request->slug) {
            $tag->setSlug($request->slug);
        }

        $tag->setMetatag($request->metatag);
        $tag->setDisplay($request->display);
        $tag->setProperties($request->properties ?? []);

        if (null !== $parent) {
            $tag->setParent($parent);
        }

        $this->tagRepository->save($tag);

        $event = TagCreatedEvent::fromTag(
            $tag->getId(),
            $tag->getCod(),
            $tag->getI18nTitle(),
            $parent?->getId()
        );
        $this->eventBus->dispatch($event);

        return new CreateTagResponse($tag);
    }
}
