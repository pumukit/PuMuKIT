<?php

declare(strict_types=1);

namespace Pumukit\CoreBundle\EventListener;

use Psr\Log\LoggerInterface;
use Pumukit\StatsBundle\Services\VisitorAnonymizer;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Exception\ExceptionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class LegacyApiListener
{
    public const DEPRECATED_SINCE = '2026-10-29';

    private const LEGACY_PATH = '#^/api/(?!v1/)#';

    private const SUCCESSORS = [
        'pumukit_schema_api_multimediaobjects' => '_api_/multimedia_objects{._format}_get_collection',
        'pumukit_schema_api_series' => '_api_/series{._format}_get_collection',
        'pumukit_schema_api_stats' => '_api_/dashboard/summary_get',
        'pumukit_schema_apirecorded_globalstats' => '_api_/dashboard/activity_get',
        'pumukit_schema_apirecorded_mmobjrecorded' => '_api_/dashboard/activity_get',
        'pumukit_schema_apirecorded_mmobjrecorded_1' => '_api_/dashboard/activity_get',
        'pumukit_schema_apirecorded_hoursrecorded' => '_api_/dashboard/activity_get',
        'pumukit_schema_apirecorded_hoursrecorded_1' => '_api_/dashboard/activity_get',
        'pumukit_stats_api_views' => '_api_/stats/timeline_get',
        'pumukit_stats_api_viewsmmobj' => '_api_/stats/timeline_get',
        'pumukit_stats_api_viewsseries' => '_api_/stats/timeline_get',
        'pumukit_stats_api_mmobjmostviewed' => 'stats_top_videos',
        'pumukit_stats_api_seriesmostviewed' => 'stats_top_series',
        'pumukit_schema_apikeywords_mmobj' => 'api_keywords_multimedia_objects',
        'pumukit_schema_apikeywords_series' => 'api_keywords_series',
        'pumukit_encoder_api_jobs' => '_api_/dashboard/jobs_get',
    ];

    public function __construct(
        private readonly LoggerInterface $legacyApiLogger,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Security $security,
        private readonly VisitorAnonymizer $visitorAnonymizer,
    ) {}

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $route = (string) $request->attributes->get('_route');
        if (!$event->isMainRequest() || '' === $route || !preg_match(self::LEGACY_PATH, $request->getPathInfo())) {
            return;
        }

        $response = $event->getResponse();
        $response->headers->set('Deprecation', '@'.(new \DateTimeImmutable(self::DEPRECATED_SINCE.' 00:00:00 UTC'))->getTimestamp());

        $successor = null;
        if (isset(self::SUCCESSORS[$route])) {
            try {
                $successor = $this->urlGenerator->generate(self::SUCCESSORS[$route]);
                $response->headers->set('Link', sprintf('<%s>; rel="successor-version"', $successor));
            } catch (ExceptionInterface) {
            }
        }

        $this->legacyApiLogger->info('Deprecated API called: {method} {path}', [
            'method' => $request->getMethod(),
            'path' => $request->getPathInfo(),
            'route' => $route,
            'status' => $response->getStatusCode(),
            'user' => $this->security->getUser()?->getUserIdentifier(),
            'ip' => $this->visitorAnonymizer->anonymizeIp($request->getClientIp()),
            'user_agent' => $request->headers->get('User-Agent'),
            'referer' => $request->headers->get('Referer'),
            'successor' => $successor,
        ]);
    }
}
