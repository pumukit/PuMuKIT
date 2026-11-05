<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DisabledFeatureListener
{
    public const PLAYLIST_ROUTES = 'pumukitnewadmin_playlist';
    public const CHAT_ROUTES = 'pumukit_live_chat_';

    public function __construct(
        private bool $enablePlaylist,
        private bool $enableChat,
    ) {}

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $route = (string) $event->getRequest()->attributes->get('_route');
        if (!$this->enablePlaylist && str_starts_with($route, self::PLAYLIST_ROUTES)) {
            throw new NotFoundHttpException('The playlists are disabled.');
        }
        if (!$this->enableChat && str_starts_with($route, self::CHAT_ROUTES)) {
            throw new NotFoundHttpException('The chat is disabled.');
        }
    }
}
