<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\Tests\Mongo;

use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;

use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;

final class MongoCommandCounter implements CommandSubscriber
{
    private const IGNORED = ['buildInfo', 'endSessions', 'hello', 'isMaster', 'ismaster', 'ping', 'saslContinue', 'saslStart'];

    private array $commands = [];

    private function __construct() {}

    public static function start(): self
    {
        $counter = new self();
        addSubscriber($counter);

        return $counter;
    }

    public function stop(): void
    {
        removeSubscriber($this);
    }

    public function count(?string $command = null, ?string $collection = null): int
    {
        return count(array_filter(
            $this->commands,
            static fn (array $entry): bool => (null === $command || $entry['command'] === $command) && (null === $collection || $entry['collection'] === $collection)
        ));
    }

    public function commands(): array
    {
        return $this->commands;
    }

    public function commandStarted(CommandStartedEvent $event): void
    {
        $name = $event->getCommandName();
        if (in_array($name, self::IGNORED, true)) {
            return;
        }

        $collection = $event->getCommand()->{$name} ?? null;
        $this->commands[] = ['command' => $name, 'collection' => is_string($collection) ? $collection : null];
    }

    public function commandSucceeded(CommandSucceededEvent $event): void {}

    public function commandFailed(CommandFailedEvent $event): void {}
}
