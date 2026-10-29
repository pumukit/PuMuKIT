<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\EventListener;

use Doctrine\ODM\MongoDB\DocumentManager;
use Jaybizzle\CrawlerDetect\CrawlerDetect;
use Psr\Log\LoggerInterface;
use Pumukit\BasePlayerBundle\Event\ViewedEvent;
use Pumukit\SchemaBundle\Document\User;
use Pumukit\StatsBundle\Document\ViewsLog;
use Pumukit\StatsBundle\Services\ViewsDailyStatsService;
use Pumukit\StatsBundle\Services\VisitorAnonymizer;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Log
{
    private $dm;
    private $requestStack;
    private $tokenStorage;
    private $crawlerDetect;
    private $visitorAnonymizer;
    private $dailyStats;
    private $logger;

    public function __construct(
        DocumentManager $documentManager,
        RequestStack $requestStack,
        TokenStorageInterface $tokenStorage,
        VisitorAnonymizer $visitorAnonymizer,
        ViewsDailyStatsService $dailyStats,
        LoggerInterface $logger
    ) {
        $this->visitorAnonymizer = $visitorAnonymizer;
        $this->dailyStats = $dailyStats;
        $this->logger = $logger;
        $this->dm = $documentManager;
        $this->requestStack = $requestStack;
        $this->tokenStorage = $tokenStorage;
        $this->crawlerDetect = new CrawlerDetect();
    }

    public function onMultimediaObjectViewed(ViewedEvent $event): void
    {
        $request = $this->requestStack->getMainRequest();
        if (!$request) {
            return;
        }

        $userAgent = $this->normalizeUserAgent($request->headers->get('user-agent'));

        if (false !== strpos($userAgent, 'TTK Zabbix Agent')) {
            return;
        }

        if ($this->crawlerDetect->isCrawler($userAgent)) {
            return;
        }

        $ip = $request->getClientIp();
        $now = new \DateTimeImmutable();
        $visitor = $this->visitorAnonymizer->visitor($ip, $userAgent, $now);
        $log = new ViewsLog(
            $request->getUri(),
            $this->visitorAnonymizer->anonymizeIp($ip),
            $userAgent,
            $request->headers->get('referer'),
            $event->getMultimediaObject()->getId(),
            $event->getMultimediaObject()->getSeries()->getId(),
            $event->getTrack() ? $event->getTrack()->id() : null,
            $this->getUser(),
            $visitor
        );

        $this->dm->persist($log);
        $this->dm->flush();

        try {
            $this->dailyStats->record(
                $now,
                (string) $event->getMultimediaObject()->getId(),
                (string) $event->getMultimediaObject()->getSeries()->getId(),
                $request->headers->get('referer'),
                $userAgent,
                $visitor,
                $request->getHost()
            );
        } catch (\Throwable $exception) {
            $this->logger->warning('Unable to update the daily view statistics: '.$exception->getMessage());
        }
    }

    private function normalizeUserAgent(?string $userAgent): string
    {
        if (null === $userAgent || '' === $userAgent) {
            return '';
        }

        if (mb_check_encoding($userAgent, 'UTF-8')) {
            return $userAgent;
        }

        $converted = mb_convert_encoding($userAgent, 'UTF-8', 'ISO-8859-1');

        return \is_string($converted) ? $converted : '';
    }

    private function getUser(): ?string
    {
        if ((null !== $token = $this->tokenStorage->getToken()) && ($user = $token->getUser()) instanceof User) {
            return $user->getUsername();
        }

        return null;
    }
}
