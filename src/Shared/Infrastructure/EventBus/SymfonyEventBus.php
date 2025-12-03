<?php

namespace App\Shared\Infrastructure\EventBus;

use App\Shared\Domain\EventBusInterface;

/**
 * Symfony adapter for the event bus interface.
 *
 * This adapter wraps Symfony's event dispatcher to keep the Domain and Application
 * layers independent from the framework.
 *
 * This is the ONLY place where Symfony's EventDispatcher is used directly.
 */
final class SymfonyEventBus implements EventBusInterface
{
    public function dispatch(object $event): void
    {
        // TODO: Implement event dispatching using Symfony's EventDispatcher
        // This will be implemented when the event system is fully integrated
    }
}

