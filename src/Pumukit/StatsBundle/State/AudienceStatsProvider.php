<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Pumukit\StatsBundle\ApiResource\StatsDevices;
use Pumukit\StatsBundle\ApiResource\StatsHeatmap;
use Pumukit\StatsBundle\ApiResource\StatsSources;
use Pumukit\StatsBundle\ApiResource\StatsSummary;
use Pumukit\StatsBundle\ApiResource\StatsTimeline;
use Pumukit\StatsBundle\ApiResource\StatsTop;
use Pumukit\StatsBundle\Services\AudienceStatsService;
use Pumukit\StatsBundle\Services\StatsQuery;
use Pumukit\StatsBundle\Services\StatsScope;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AudienceStatsProvider implements ProviderInterface
{
    private const DEFAULT_DAYS = 1;
    private const MAX_DAYS = 731;
    private const MAX_LIMIT = 50;

    public function __construct(
        private readonly AudienceStatsService $stats,
        private readonly StatsScope $scope,
    ) {}

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object
    {
        $request = $context['request'] ?? null;
        if (!$request instanceof Request) {
            throw new BadRequestHttpException('Missing request.');
        }

        $query = $this->query($request);

        return match ($operation->getClass()) {
            StatsSummary::class => new StatsSummary(...$this->stats->summary($query)),
            StatsTimeline::class => new StatsTimeline(...$this->stats->timeline($query, $request->query->getString('interval') ?: null)),
            StatsTop::class => new StatsTop(...$this->stats->top(
                $query,
                'stats_top_series' === $operation->getName() ? 'series' : 'multimediaObject',
                max(1, min(self::MAX_LIMIT, $request->query->getInt('limit', 10))),
                $request->getLocale(),
            )),
            StatsSources::class => new StatsSources(...$this->stats->sources($query)),
            StatsDevices::class => new StatsDevices(...$this->stats->devices($query)),
            StatsHeatmap::class => new StatsHeatmap(...$this->stats->heatmap($query)),
            default => throw new NotFoundHttpException(),
        };
    }

    private function query(Request $request): StatsQuery
    {
        $today = new \DateTimeImmutable('today');
        $to = $this->date($request->query->getString('to')) ?? $today;
        $from = $this->date($request->query->getString('from')) ?? $to->modify('-'.(self::DEFAULT_DAYS - 1).' days');
        if ($to > $today) {
            $to = $today;
        }
        if ($from > $to) {
            throw new BadRequestHttpException('"from" must not be after "to".');
        }
        if ($from->diff($to)->days >= self::MAX_DAYS) {
            throw new BadRequestHttpException(sprintf('The range cannot be longer than %d days.', self::MAX_DAYS));
        }

        $multimediaObjectId = $this->id($request->query->getString('multimediaObject'));
        $seriesId = $this->id($request->query->getString('series'));

        if (null !== $multimediaObjectId && !$this->scope->canSeeMultimediaObject($multimediaObjectId)) {
            throw new NotFoundHttpException();
        }
        if (null !== $seriesId && !$this->scope->canSeeSeries($seriesId)) {
            throw new NotFoundHttpException();
        }

        return new StatsQuery($from, $to, $seriesId, $multimediaObjectId);
    }

    private function date(string $value): ?\DateTimeImmutable
    {
        if ('' === $value) {
            return null;
        }
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (!$date || $date->format('Y-m-d') !== $value) {
            throw new BadRequestHttpException(sprintf('Invalid date "%s", expected YYYY-MM-DD.', $value));
        }

        return $date;
    }

    private function id(string $value): ?string
    {
        if ('' === $value) {
            return null;
        }
        if (!preg_match('/^[0-9a-f]{24}$/', $value)) {
            throw new BadRequestHttpException(sprintf('Invalid id "%s".', $value));
        }

        return $value;
    }
}
