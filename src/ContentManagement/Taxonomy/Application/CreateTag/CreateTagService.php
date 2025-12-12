<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\CreateTag;

use App\ContentManagement\Taxonomy\Domain\Event\TagCreatedEvent;
use App\ContentManagement\Taxonomy\Domain\Exception\TagAlreadyExistsException;
use App\ContentManagement\Taxonomy\Domain\Exception\TagNotFoundException;
use App\ContentManagement\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Pumukit\SchemaBundle\Document\Tag;

final readonly class CreateTagService
{
    public function __construct(
        private TagRepositoryInterface $tagRepository,
        private EventBusInterface $eventBus
    ) {}

    public function __invoke(CreateTagRequest $request): CreateTagResponse
    {
        $existingTag = $this->tagRepository->findByCod($request->cod);
        if ($existingTag) {
            throw TagAlreadyExistsException::withCod($request->cod);
        }

        $parent = null;
        if ($request->parentId) {
            $parent = $this->tagRepository->find($request->parentId);
            if (!$parent) {
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

        if ($parent) {
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
