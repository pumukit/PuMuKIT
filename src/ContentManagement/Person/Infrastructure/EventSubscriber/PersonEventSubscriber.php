<?php

declare(strict_types=1);

namespace App\ContentManagement\Person\Infrastructure\EventSubscriber;

use App\ContentManagement\Person\Domain\Event\PersonCreated;
use App\ContentManagement\Person\Domain\Event\PersonDeleted;
use App\ContentManagement\Person\Domain\Event\PersonUpdated;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class PersonEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            PersonCreated::class => 'onPersonCreated',
            PersonUpdated::class => 'onPersonUpdated',
            PersonDeleted::class => 'onPersonDeleted',
        ];
    }

    public function onPersonCreated(PersonCreated $event): void
    {
        $this->logger->info('Person created', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }

    public function onPersonUpdated(PersonUpdated $event): void
    {
        $this->logger->info('Person updated', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }

    public function onPersonDeleted(PersonDeleted $event): void
    {
        $this->logger->info('Person deleted', [
            'id' => $event->id,
            'name' => $event->name,
        ]);
    }
}
