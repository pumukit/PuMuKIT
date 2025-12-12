<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\EventBus;

use App\Shared\Domain\EventBusInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class SymfonyEventBus implements EventBusInterface
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    public function dispatch(object $event): void
    {
        $this->eventDispatcher->dispatch($event, $event::class);
    }
}
