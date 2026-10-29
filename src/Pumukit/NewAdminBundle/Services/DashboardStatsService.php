<?php

declare(strict_types=1);

namespace Pumukit\NewAdminBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Pumukit\EncoderBundle\Document\Job;
use Pumukit\EncoderBundle\Services\ProfileService;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\StatsBundle\Services\StatsScope;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final class DashboardStatsService
{
    private const CACHE_TTL = 300;
    private const ACTIVITY_MONTHS = 12;
    private const PLAYABLE_TYPES = [MultimediaObject::TYPE_UNKNOWN, MultimediaObject::TYPE_VIDEO, MultimediaObject::TYPE_AUDIO];

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly StatsScope $scope,
        private readonly ProfileService $profileService,
        private readonly CacheInterface $cache,
    ) {}

    public function summary(): array
    {
        return $this->cached('summary', function (): array {
            $criteria = $this->catalogueCriteria();
            $totals = $this->multimediaObjects()->aggregate([
                ['$match' => $criteria],
                ['$group' => ['_id' => null, 'count' => ['$sum' => 1], 'duration' => ['$sum' => '$duration']]],
            ])->toArray()[0] ?? [];

            if (null === $this->scope->multimediaObjectIds()) {
                $series = $this->documentManager->getDocumentCollection(Series::class)->countDocuments(['type' => ['$ne' => Series::TYPE_PLAYLIST]]);
            } else {
                $series = count($this->multimediaObjects()->distinct('series', $criteria));
            }

            return [
                'series' => $series,
                'multimediaObjects' => $totals['count'] ?? 0,
                'duration' => (int) ($totals['duration'] ?? 0),
            ];
        });
    }

    public function activity(): array
    {
        return $this->cached('activity', function (): array {
            $months = [];
            $first = new \DateTimeImmutable('first day of this month 00:00');
            $first = $first->modify('-'.(self::ACTIVITY_MONTHS - 1).' months');
            for ($month = $first, $i = 0; $i < self::ACTIVITY_MONTHS; $month = $month->modify('+1 month'), ++$i) {
                $months[$month->format('Y-m')] = ['month' => $month->format('Y-m'), 'count' => 0, 'duration' => 0];
            }

            foreach ($this->multimediaObjects()->aggregate([
                ['$match' => $this->catalogueCriteria() + ['properties.created' => ['$type' => 'string', '$gte' => $first->format('Y-m')]]],
                ['$group' => [
                    '_id' => ['$substrCP' => ['$properties.created', 0, 7]],
                    'count' => ['$sum' => 1],
                    'duration' => ['$sum' => '$duration'],
                ]],
            ]) as $row) {
                if (isset($months[$row['_id']])) {
                    $months[$row['_id']]['count'] = $row['count'];
                    $months[$row['_id']]['duration'] = (int) $row['duration'];
                }
            }

            return ['months' => array_values($months)];
        });
    }

    public function status(): array
    {
        return $this->cached('status', function (): array {
            $criteria = $this->catalogueCriteria();
            $count = fn (array $extra): int => $this->multimediaObjects()->countDocuments($extra + $criteria);

            return ['items' => [
                ['key' => 'published', 'count' => $count(['status' => MultimediaObject::STATUS_PUBLISHED])],
                ['key' => 'hidden', 'count' => $count(['status' => MultimediaObject::STATUS_HIDDEN])],
                ['key' => 'blocked', 'count' => $count(['status' => MultimediaObject::STATUS_BLOCKED])],
                ['key' => 'withoutMedia', 'count' => $count(['type' => ['$in' => self::PLAYABLE_TYPES], 'tracks.tags' => ['$ne' => 'display']])],
            ]];
        });
    }

    public function jobs(): array
    {
        $jobs = $this->documentManager->getDocumentCollection(Job::class);
        $since = new UTCDateTime(new \DateTimeImmutable('-24 hours'));

        return ['items' => [
            ['key' => 'waiting', 'count' => $jobs->countDocuments(['status' => Job::STATUS_WAITING])],
            ['key' => 'executing', 'count' => $jobs->countDocuments(['status' => Job::STATUS_EXECUTING])],
            ['key' => 'paused', 'count' => $jobs->countDocuments(['status' => Job::STATUS_PAUSED])],
            ['key' => 'finished', 'count' => $jobs->countDocuments(['status' => Job::STATUS_FINISHED, 'timeend' => ['$gte' => $since]])],
            ['key' => 'error', 'count' => $jobs->countDocuments(['status' => Job::STATUS_ERROR, 'timeend' => ['$gte' => $since]])],
        ]];
    }

    public function storage(): array
    {
        $items = [];
        foreach ($this->profileService->getDirOutInfo() as $share) {
            $total = (int) $share['total'];
            if ($total <= 0) {
                continue;
            }
            $device = @stat($share['dir'])['dev'] ?? $share['dir'];
            $items[$device] ??= ['dirs' => [], 'total' => $total, 'used' => $total - (int) $share['free']];
            $items[$device]['dirs'][] = $share['dir'];
        }

        return ['items' => array_values($items)];
    }

    private function catalogueCriteria(): array
    {
        $criteria = [
            'status' => ['$nin' => [MultimediaObject::STATUS_PROTOTYPE, MultimediaObject::STATUS_NEW]],
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
        ];
        $scope = $this->scope->multimediaObjectIds();
        if (null !== $scope) {
            $criteria['_id'] = ['$in' => $scope];
        }

        return $criteria;
    }

    private function cached(string $report, callable $compute): array
    {
        $scope = $this->scope->multimediaObjectIds();
        $key = 'pumukit_dashboard_'.$report.'_'.(null === $scope ? 'global' : md5(implode(',', array_map('strval', $scope))));

        return $this->cache->get($key, static function (ItemInterface $item) use ($compute): array {
            $item->expiresAfter(self::CACHE_TTL);

            return $compute();
        });
    }

    private function multimediaObjects(): Collection
    {
        return $this->documentManager->getDocumentCollection(MultimediaObject::class);
    }
}
