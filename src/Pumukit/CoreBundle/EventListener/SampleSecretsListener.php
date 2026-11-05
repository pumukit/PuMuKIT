<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Pumukit\CoreBundle\Services\SampleSecretsChecker;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\HttpKernel\Event\RequestEvent;

final class SampleSecretsListener
{
    private const BUILD_COMMANDS = '/^(cache|assets|importmap|asset-map|secrets|lint|debug|completion):|^(list|help|about|_complete|pumukit:check:secrets)$/';

    public function __construct(
        private readonly SampleSecretsChecker $checker,
        private readonly string $environment,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if ($event->isMainRequest()) {
            $this->check();
        }
    }

    public function onConsoleCommand(ConsoleCommandEvent $event): void
    {
        if (1 !== preg_match(self::BUILD_COMMANDS, (string) $event->getCommand()?->getName())) {
            $this->check();
        }
    }

    private function check(): void
    {
        if ('prod' !== $this->environment) {
            return;
        }

        $found = $this->checker->sampleValues();
        if ([] !== $found) {
            throw new \RuntimeException(sprintf('PuMuKIT does not run in production with the sample values of: %s.', implode(', ', $found)));
        }
    }
}
