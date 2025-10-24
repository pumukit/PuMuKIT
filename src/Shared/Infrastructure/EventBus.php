<?php

namespace App\Shared\Infrastructure;

use App\Shared\Domain\EventBusInterface;

final class EventBus implements EventBusInterface
{
    public function dispatch(object $event): void {}
}
