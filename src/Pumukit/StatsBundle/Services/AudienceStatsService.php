<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use Pumukit\SchemaBundle\Document\MultimediaObject;
use Pumukit\SchemaBundle\Document\Series;
use Pumukit\StatsBundle\Document\ViewsDailyStats;
use Pumukit\StatsBundle\Document\ViewsDailyTotals;

final class AudienceStatsService
{
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly StatsScope $scope,
    ) {}

    public function summary(StatsQuery $query): array
    {
        $current = $this->totals($query, true);
        $previous = $this->totals($query->previous(), false);

        $summary = [
            'views' => $current['views'],
            'previousViews' => $previous['views'],
            'uniqueViews' => $current['uniqueViews'],
            'previousUniqueViews' => $previous['uniqueViews'],
            'viewedObjects' => count($current['objects']),
            'viewedSeries' => $current['series'],
            'publishedObjects' => null,
            'unviewedObjects' => null,
        ];

        if (null === $query->multimediaObjectId) {
            $published = $this->publishedCriteria($query);
            $summary['publishedObjects'] = $this->multimediaObjects()->countDocuments($published);
            $unviewed = $published;
            $unviewed['_id'] = ($published['_id'] ?? []) + ['$nin' => $current['objects']];
            $summary['unviewedObjects'] = $this->multimediaObjects()->countDocuments($unviewed);
        }

        return $summary;
    }

    public function timeline(StatsQuery $query, ?string $requestedInterval = null): array
    {
        $interval = 1 === $query->days() ? 'hour' : $query->interval($requestedInterval);
        $previousQuery = $query->previous();

        if ('hour' === $interval) {
            $current = $this->viewsByHour($query);
            $previous = $this->viewsByHour($previousQuery);
            $points = [];
            for ($hour = 0; $hour < 24; ++$hour) {
                $points[] = [
                    'date' => sprintf('%s %02d:00', $query->from->format('Y-m-d'), $hour),
                    'views' => $current[$hour] ?? 0,
                    'previousDate' => sprintf('%s %02d:00', $previousQuery->from->format('Y-m-d'), $hour),
                    'previousViews' => $previous[$hour] ?? 0,
                ];
            }

            return ['interval' => $interval, 'points' => $points];
        }

        $current = $this->viewsByBucket($query, $interval);
        $previous = $this->viewsByBucket($previousQuery, $interval);
        $previousBuckets = $previousQuery->buckets($interval);

        $points = [];
        foreach ($query->buckets($interval) as $index => $bucket) {
            $previousBucket = $previousBuckets[$index] ?? null;
            $points[] = [
                'date' => $bucket,
                'views' => $current[$bucket] ?? 0,
                'previousDate' => $previousBucket,
                'previousViews' => null !== $previousBucket ? ($previous[$previousBucket] ?? 0) : 0,
            ];
        }

        return ['interval' => $interval, 'points' => $points];
    }

    public function top(StatsQuery $query, string $field, int $limit, string $locale): array
    {
        $match = $this->match($query);
        $total = $this->sum($match, 'views');
        $rows = $this->stats()->aggregate([
            ['$match' => $match],
            ['$group' => ['_id' => '$'.$field, 'views' => ['$sum' => '$views']]],
            ['$sort' => ['views' => -1, '_id' => 1]],
            ['$limit' => $limit],
        ])->toArray();

        $ids = array_map(static fn ($row) => $row['_id'], $rows);
        $previous = [];
        foreach ($this->stats()->aggregate([
            ['$match' => $this->match($query->previous()) + [$field => ['$in' => $ids]]],
            ['$group' => ['_id' => '$'.$field, 'views' => ['$sum' => '$views']]],
        ]) as $row) {
            $previous[(string) $row['_id']] = $row['views'];
        }

        $class = 'series' === $field ? Series::class : MultimediaObject::class;
        $titles = [];
        foreach ($this->documentManager->getRepository($class)->findBy(['_id' => ['$in' => $ids]]) as $document) {
            $titles[(string) $document->getId()] = $document->getTitle($locale) ?: $document->getTitle();
        }

        $items = [];
        foreach ($rows as $row) {
            $id = (string) $row['_id'];
            $items[] = [
                'id' => $id,
                'title' => $titles[$id] ?? null,
                'views' => $row['views'],
                'previousViews' => $previous[$id] ?? 0,
                'share' => $total > 0 ? round($row['views'] / $total, 4) : 0,
            ];
        }

        return ['total' => $total, 'items' => $items];
    }

    public function sources(StatsQuery $query): array
    {
        return $this->breakdown($query, 'sources', ViewClassifier::SOURCES);
    }

    public function devices(StatsQuery $query): array
    {
        return $this->breakdown($query, 'devices', ViewClassifier::DEVICES);
    }

    public function heatmap(StatsQuery $query): array
    {
        $cells = array_fill(0, 7, array_fill(0, 24, 0));
        [$collection, $match] = $this->source($query);
        foreach ($collection->aggregate([
            ['$match' => $match],
            ['$project' => ['date' => 1, 'hours' => ['$objectToArray' => ['$ifNull' => ['$hours', (object) []]]]]],
            ['$unwind' => '$hours'],
            ['$group' => [
                '_id' => [
                    'day' => ['$isoDayOfWeek' => ['date' => '$date', 'timezone' => date_default_timezone_get()]],
                    'hour' => '$hours.k',
                ],
                'views' => ['$sum' => '$hours.v'],
            ]],
        ]) as $row) {
            $cells[$row['_id']['day'] - 1][(int) $row['_id']['hour']] = $row['views'];
        }

        return ['cells' => $cells, 'max' => max(array_map('max', $cells))];
    }

    private function totals(StatsQuery $query, bool $withDistinct): array
    {
        [$collection, $match] = $this->source($query);
        $sums = $collection->aggregate([
            ['$match' => $match],
            ['$group' => ['_id' => null, 'views' => ['$sum' => '$views'], 'uniqueViews' => ['$sum' => '$uniqueViews']]],
        ])->toArray()[0] ?? [];

        $totals = ['views' => $sums['views'] ?? 0, 'uniqueViews' => $sums['uniqueViews'] ?? 0, 'objects' => [], 'series' => 0];
        if ($withDistinct) {
            $match = $this->match($query);
            $totals['objects'] = array_map(static fn ($row) => $row['_id'], $this->stats()->aggregate([
                ['$match' => $match],
                ['$group' => ['_id' => '$multimediaObject']],
            ])->toArray());
            $totals['series'] = $this->stats()->aggregate([
                ['$match' => $match],
                ['$group' => ['_id' => '$series']],
                ['$count' => 'value'],
            ])->toArray()[0]['value'] ?? 0;
        }

        return $totals;
    }

    private function source(StatsQuery $query): array
    {
        if (null === $query->seriesId && null === $query->multimediaObjectId && null === $this->scope->multimediaObjectIds()) {
            return [$this->dailyTotals(), ['date' => $this->match($query)['date']]];
        }

        return [$this->stats(), $this->match($query)];
    }

    private function breakdown(StatsQuery $query, string $field, array $keys): array
    {
        $group = ['_id' => null];
        foreach ($keys as $key) {
            $group[$key] = ['$sum' => '$'.$field.'.'.$key];
        }
        [$collection, $match] = $this->source($query);
        $row = $collection->aggregate([['$match' => $match], ['$group' => $group]])->toArray()[0] ?? [];

        $counts = [];
        foreach ($keys as $key) {
            $counts[$key] = $row[$key] ?? 0;
        }
        $total = array_sum($counts);

        return [
            'total' => $total,
            'items' => array_map(static fn ($key) => [
                'key' => $key,
                'views' => $counts[$key],
                'share' => $total > 0 ? round($counts[$key] / $total, 4) : 0,
            ], $keys),
        ];
    }

    private function viewsByHour(StatsQuery $query): array
    {
        $hours = [];
        [$collection, $match] = $this->source($query);
        foreach ($collection->aggregate([
            ['$match' => $match],
            ['$project' => ['hours' => ['$objectToArray' => ['$ifNull' => ['$hours', (object) []]]]]],
            ['$unwind' => '$hours'],
            ['$group' => ['_id' => '$hours.k', 'views' => ['$sum' => '$hours.v']]],
        ]) as $row) {
            $hours[(int) $row['_id']] = $row['views'];
        }

        return $hours;
    }

    private function viewsByBucket(StatsQuery $query, string $interval): array
    {
        $timezone = new \DateTimeZone(date_default_timezone_get());
        $views = [];
        [$collection, $match] = $this->source($query);
        foreach ($collection->aggregate([
            ['$match' => $match],
            ['$group' => ['_id' => '$date', 'views' => ['$sum' => '$views']]],
        ]) as $row) {
            $day = \DateTimeImmutable::createFromInterface($row['_id']->toDateTime())->setTimezone($timezone);
            $bucket = (match ($interval) {
                'week' => $day->modify('monday this week'),
                'month' => $day->modify('first day of this month'),
                default => $day,
            })->format('Y-m-d');
            $views[$bucket] = ($views[$bucket] ?? 0) + $row['views'];
        }

        return $views;
    }

    private function sum(array $match, string $field): int
    {
        return $this->stats()->aggregate([['$match' => $match], ['$group' => ['_id' => null, 'value' => ['$sum' => '$'.$field]]]])->toArray()[0]['value'] ?? 0;
    }

    private function match(StatsQuery $query): array
    {
        $match = ['date' => [
            '$gte' => new UTCDateTime($query->start()),
            '$lte' => new UTCDateTime($query->to->setTime(0, 0)),
        ]];

        if (null !== $query->multimediaObjectId) {
            $match['multimediaObject'] = new ObjectId($query->multimediaObjectId);
        } elseif (null !== $query->seriesId) {
            $match['series'] = new ObjectId($query->seriesId);
        }

        $scope = $this->scope->multimediaObjectIds();
        if (null !== $scope && null === $query->multimediaObjectId) {
            $match['multimediaObject'] = ['$in' => $scope];
        }

        return $match;
    }

    private function publishedCriteria(StatsQuery $query): array
    {
        $criteria = [
            'status' => MultimediaObject::STATUS_PUBLISHED,
            'type' => ['$ne' => MultimediaObject::TYPE_LIVE],
        ];
        if (null !== $query->seriesId) {
            $criteria['series'] = new ObjectId($query->seriesId);
        }
        $scope = $this->scope->multimediaObjectIds();
        if (null !== $scope) {
            $criteria['_id'] = ['$in' => $scope];
        }

        return $criteria;
    }

    private function dailyTotals(): Collection
    {
        return $this->documentManager->getDocumentCollection(ViewsDailyTotals::class);
    }

    private function stats(): Collection
    {
        return $this->documentManager->getDocumentCollection(ViewsDailyStats::class);
    }

    private function multimediaObjects(): Collection
    {
        return $this->documentManager->getDocumentCollection(MultimediaObject::class);
    }
}
