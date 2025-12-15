<?php

declare(strict_types=1);

namespace App\Taxonomy\Tag\Infrastructure\EventSubscriber;

use App\Taxonomy\Tag\Domain\Event\TagCreatedEvent;
use App\Taxonomy\Tag\Domain\Event\TagDeletedEvent;
use App\Taxonomy\Tag\Domain\Event\TagUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class TagEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            TagCreatedEvent::class => 'onTagCreated',
            TagUpdatedEvent::class => 'onTagUpdated',
            TagDeletedEvent::class => 'onTagDeleted',
        ];
    }

    public function onTagCreated(TagCreatedEvent $event): void
    {
        $this->logger->info('Tag created', [
            'tag_id' => $event->tagId,
            'cod' => $event->cod,
            'title' => $event->title,
            'parent_id' => $event->parentId,
            'occurred_on' => $event->occurredOn->format('c'),
        ]);
    }

    public function onTagUpdated(TagUpdatedEvent $event): void
    {
        $this->logger->info('Tag updated', [
            'tag_id' => $event->tagId,
            'cod' => $event->cod,
            'title' => $event->title,
            'occurred_on' => $event->occurredOn->format('c'),
        ]);
    }

    public function onTagDeleted(TagDeletedEvent $event): void
    {
        $this->logger->info('Tag deleted', [
            'tag_id' => $event->tagId,
            'cod' => $event->cod,
            'occurred_on' => $event->occurredOn->format('c'),
        ]);
    }
}
