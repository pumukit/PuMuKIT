<?php

declare(strict_types=1);

namespace App\ContentManagement\Taxonomy\Application\UpdateTag;

use Pumukit\SchemaBundle\Document\Tag;
use App\ContentManagement\Taxonomy\Domain\Event\TagUpdatedEvent;
use App\ContentManagement\Taxonomy\Domain\Exception\TagNotFoundException;
use App\ContentManagement\Taxonomy\Domain\Repository\TagRepositoryInterface;
use App\Shared\Domain\EventBusInterface;

final class UpdateTagService
{
    public function __construct(
        private readonly TagRepositoryInterface $tagRepository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(UpdateTagRequest $request): UpdateTagResponse
    {
        UpdateTagValidator::validate($request);

        $tag = $this->tagRepository->find($request->id);
        if (!$tag instanceof Tag) {
            throw TagNotFoundException::withId($request->id);
        }

        $tag->setI18nTitle($request->title);
        $tag->setI18nDescription($request->description);

        if ($request->slug) {
            $tag->setSlug($request->slug);
        }

        $tag->setMetatag($request->metatag);
        $tag->setDisplay($request->display);

        $tag->setProperties($request->properties);

        $this->tagRepository->save($tag);

        $event = TagUpdatedEvent::fromTag(
            $tag->getId(),
            $tag->getCod(),
            $tag->getI18nTitle()
        );
        $this->eventBus->dispatch($event);

        return new UpdateTagResponse($tag);
    }
}
