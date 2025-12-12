<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\EventSubscriber;

use App\ContentManagement\Person\Domain\Event\PersonCreatedEvent;
use App\ContentManagement\Person\Domain\Event\PersonDeletedEvent;
use App\ContentManagement\Person\Domain\Event\PersonUpdatedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final class PersonEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PersonCreatedEvent::class => 'onPersonCreated',
            PersonUpdatedEvent::class => 'onPersonUpdated',
            PersonDeletedEvent::class => 'onPersonDeleted',
        ];
    }

    public function onPersonCreated(PersonCreatedEvent $event): void
    {
        $this->logger->info('Person created', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }

    public function onPersonUpdated(PersonUpdatedEvent $event): void
    {
        $this->logger->info('Person updated', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }

    public function onPersonDeleted(PersonDeletedEvent $event): void
    {
        $this->logger->info('Person deleted', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }
}
