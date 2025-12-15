<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Ui\Backoffice\Menu;

use App\Shared\Infrastructure\Ui\Backoffice\Menu\Event\MenuBuildEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

final class MenuBuilder
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private UrlGeneratorInterface $urlGenerator,
        private AuthorizationCheckerInterface $authorizationChecker,
        private RequestStack $requestStack
    ) {}

    public function buildMenu(): array
    {
        $currentRoute = $this->requestStack->getCurrentRequest()?->attributes->get('_route');

        $event = new MenuBuildEvent($currentRoute);

        $this->eventDispatcher->dispatch($event, MenuBuildEvent::NAME);

        $hierarchy = $event->buildHierarchy();

        return $this->processHierarchy($hierarchy, $currentRoute);
    }

    private function processHierarchy(array $items, ?string $currentRoute): array
    {
        $processed = [];

        foreach ($items as $item) {
            if (null !== $item['permission'] && !$this->authorizationChecker->isGranted($item['permission'])) {
                continue;
            }

            $item['children'] = $this->processHierarchy($item['children'], $currentRoute);

            if (empty($item['children']) && null === $item['route']) {
                continue;
            }

            if (null !== $item['route']) {
                $item['url'] = $this->urlGenerator->generate($item['route'], $item['route_params']);
                $item['is_active'] = $this->isActiveRoute($currentRoute, $item['route'], $item['active_routes'] ?? []);
            } else {
                $item['url'] = '#';
                $item['is_active'] = $this->hasActiveChild($item['children']);
            }

            $processed[] = $item;
        }

        return $processed;
    }

    private function hasActiveChild(array $children): bool
    {
        foreach ($children as $child) {
            if ($child['is_active'] ?? false) {
                return true;
            }
            if (empty($child['children'])) {
                continue;
            }
            if (!$this->hasActiveChild($child['children'])) {
                continue;
            }
            return true;
        }

        return false;
    }

    private function isActiveRoute(?string $currentRoute, string $mainRoute, array $activeRoutes): bool
    {
        if ($currentRoute === $mainRoute) {
            return true;
        }

        return in_array($currentRoute, $activeRoutes, true);
    }
}
