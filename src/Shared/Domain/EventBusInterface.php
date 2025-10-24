<?php

namespace App\Shared\Domain;

interface EventBusInterface
{
    public function dispatch(object $event): void;
}
