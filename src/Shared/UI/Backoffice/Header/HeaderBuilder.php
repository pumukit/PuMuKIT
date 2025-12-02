<?php

declare(strict_types=1);

namespace App\Shared\UI\Backoffice\Header;

use App\Shared\UI\Backoffice\Header\Event\HeaderBuildEvent;
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
            // Check permission
            if ($item['permission'] !== null && !$this->authorizationChecker->isGranted($item['permission'])) {
                continue;
            }

            // Generate URL if route is provided
            if ($item['route'] !== null) {
                $item['url'] = $this->urlGenerator->generate($item['route'], $item['route_params']);
            } else {
                $item['url'] = null;
            }

            $processed[] = $item;
        }

        return $processed;
    }
}

