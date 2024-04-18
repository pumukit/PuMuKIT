<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\StatsBundle\Document\ViewsLog;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Log
{
    private $dm;
    private $requestStack;
    private $tokenStorage;
    private $crawlerDetect;

    public function __construct(
        DocumentManager $documentManager,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage
    ) {
        $this->dm = $documentManager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->crawlerDetect = new CrawlerDetect();
    }

    public function onMultimediaObjectViewed(ViewedEvent $event): void
    {
        $request = $this->requestStack->getMasterRequest();
        if (!$request) {
            return;
        }

        $userAgent = mb_convert_encoding($request->headers->get('user-agent'), 'UTF-8', 'ISO-8859-1');

        if (false !== strpos($userAgent, 'TTK Zabbix Agent')) {
            return;
        }

        if ($this->crawlerDetect->isCrawler($userAgent)) {
            return;
        }

        $log = new ViewsLog(
            $request->getUri(),
            $request->getClientIp(),
            $userAgent,
            $request->headers->get('referer'),
            $event->getMultimediaObject()->getId(),
            $event->getMultimediaObject()->getSeries()->getId(),
            $event->getTrack() ? $event->getTrack()->id() : null,
            $this->getUser()
        );

        $this->dm->persist($log);
        $this->dm->flush();
    }

    private function getUser(): ?string
    {
        if ((null !== $token = $this->tokenStorage->getToken()) && ($user = $token->getUser()) instanceof User) {
            return $user->getUsername();
        }

        return null;
    }
}
