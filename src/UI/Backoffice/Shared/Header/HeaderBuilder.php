<?php

declare(strict_types=1);

namespace App\UI\Backoffice\Shared\Header;

use App\UI\Backoffice\Shared\Header\Event\HeaderBuildEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class HeaderBuilder
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UrlGeneratorInterface $urlGenerator,
        private AuthorizationCheckerInterface $authorizationChecker
    ) {}

    public function buildHeader(): array
    {
        $event = new HeaderBuildEvent();

        $this->eventDispatcher->dispatch($event, HeaderBuildEvent::NAME);

        return $this->processItems($event->getItems());
    }

    private function processItems(array $items): array
    {
        $processed = [];

        foreach ($items as $item) {
            if (null !== $item['permission'] && !$this->authorizationChecker->isGranted($item['permission'])) {
                continue;
            }

            $item['url'] = null !== $item['route'] ? $this->urlGenerator->generate($item['route'], $item['route_params']) : null;

            $processed[] = $item;
        }

        return $processed;
    }
}
