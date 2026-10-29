<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Services;

use Doctrine\ODM\MongoDB\DocumentManager;
use MongoDB\BSON\ObjectId;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Driver\Exception\BulkWriteException;
use Pumukit\StatsBundle\Document\ViewsDailyStats;
use Pumukit\StatsBundle\Document\ViewsDailyTotals;
use Pumukit\StatsBundle\Document\ViewsDailyVisitor;
use Pumukit\StatsBundle\Document\ViewsLog;

final class ViewsDailyStatsService
{
    private const DUPLICATE_KEY = 11000;
    private const VISITOR_TTL = '+3 days';

    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly ViewClassifier $classifier,
    ) {}

    public function record(\DateTimeInterface $date, string $multimediaObject, string $series, ?string $referer, ?string $userAgent, string $visitor, ?string $requestHost = null): void
    {
        $day = $this->day($date);
        $local = \DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone(date_default_timezone_get()));
        $multimediaObjectId = new ObjectId($multimediaObject);

        $unique = true;

        try {
            $this->visitors()->insertOne([
                'date' => $day,
                'multimediaObject' => $multimediaObjectId,
                'visitor' => $visitor,
                'expiresAt' => new UTCDateTime($local->modify(self::VISITOR_TTL)),
            ]);
        } catch (BulkWriteException $exception) {
            if (self::DUPLICATE_KEY !== $exception->getWriteResult()->getWriteErrors()[0]?->getCode()) {
                throw $exception;
            }
            $unique = false;
        }

        $update = [
            '$inc' => [
                'views' => 1,
                'uniqueViews' => $unique ? 1 : 0,
                'sources.'.$this->classifier->source($referer, $requestHost) => 1,
                'devices.'.$this->classifier->device($userAgent) => 1,
                'hours.'.(int) $local->format('G') => 1,
            ],
            '$setOnInsert' => ['series' => new ObjectId($series)],
        ];

        $this->upsert($this->stats(), ['date' => $day, 'multimediaObject' => $multimediaObjectId], $update);
        $this->upsert($this->totals(), ['date' => $day], ['$inc' => $update['$inc']]);
    }

    public function rebuildDay(\DateTimeImmutable $day, bool $withVisitors = false): int
    {
        $timezone = date_default_timezone_get();
        $start = $day->setTime(0, 0);
        $match = ['date' => ['$gte' => new UTCDateTime($start), '$lt' => new UTCDateTime($start->modify('+1 day'))]];
        $visitorKey = ['$ifNull' => ['$visitor', ['$concat' => [['$ifNull' => ['$ip', '']], '|', ['$ifNull' => ['$userAgent', '']]]]]];

        $documents = [];
        foreach ($this->logs()->aggregate([
            ['$match' => $match],
            ['$group' => [
                '_id' => [
                    'multimediaObject' => '$multimediaObject',
                    'series' => '$series',
                    'referer' => '$referer',
                    'userAgent' => '$userAgent',
                    'hour' => ['$hour' => ['date' => '$date', 'timezone' => $timezone]],
                ],
                'views' => ['$sum' => 1],
            ]],
        ], ['allowDiskUse' => true]) as $row) {
            if (null === $row['_id']['multimediaObject']) {
                continue;
            }
            $key = (string) $row['_id']['multimediaObject'];
            $documents[$key] ??= [
                'date' => new UTCDateTime($start),
                'multimediaObject' => $row['_id']['multimediaObject'],
                'series' => $row['_id']['series'],
                'views' => 0,
                'uniqueViews' => 0,
                'sources' => [],
                'devices' => [],
                'hours' => [],
            ];
            $document = &$documents[$key];
            $views = $row['views'];
            $source = $this->classifier->source($row['_id']['referer'] ?? null);
            $device = $this->classifier->device($row['_id']['userAgent'] ?? null);
            $hour = (string) $row['_id']['hour'];
            $document['views'] += $views;
            $document['sources'][$source] = ($document['sources'][$source] ?? 0) + $views;
            $document['devices'][$device] = ($document['devices'][$device] ?? 0) + $views;
            $document['hours'][$hour] = ($document['hours'][$hour] ?? 0) + $views;
            unset($document);
        }

        $visitors = [];
        foreach ($this->logs()->aggregate([
            ['$match' => $match],
            ['$group' => ['_id' => ['multimediaObject' => '$multimediaObject', 'visitor' => $visitorKey]]],
        ], ['allowDiskUse' => true]) as $row) {
            $key = (string) $row['_id']['multimediaObject'];
            if (isset($documents[$key])) {
                ++$documents[$key]['uniqueViews'];
                if ($withVisitors) {
                    $visitors[] = [
                        'date' => new UTCDateTime($start),
                        'multimediaObject' => $row['_id']['multimediaObject'],
                        'visitor' => $row['_id']['visitor'],
                        'expiresAt' => new UTCDateTime($start->modify('+1 day')->modify(self::VISITOR_TTL)),
                    ];
                }
            }
        }

        foreach ($documents as &$document) {
            $document['hours'] = (object) $document['hours'];
        }
        unset($document);

        $totals = ['date' => new UTCDateTime($start), 'views' => 0, 'uniqueViews' => 0, 'sources' => [], 'devices' => [], 'hours' => []];
        foreach ($documents as $document) {
            $totals['views'] += $document['views'];
            $totals['uniqueViews'] += $document['uniqueViews'];
            foreach (['sources', 'devices', 'hours'] as $field) {
                foreach ((array) $document[$field] as $key => $value) {
                    $totals[$field][$key] = ($totals[$field][$key] ?? 0) + $value;
                }
            }
        }
        $totals['hours'] = (object) $totals['hours'];

        $this->stats()->deleteMany(['date' => new UTCDateTime($start)]);
        $this->totals()->deleteMany(['date' => new UTCDateTime($start)]);
        if ($documents) {
            $this->stats()->insertMany(array_values($documents));
            $this->totals()->insertOne($totals);
        }
        if ($visitors) {
            $this->visitors()->deleteMany(['date' => new UTCDateTime($start)]);

            try {
                $this->visitors()->insertMany($visitors, ['ordered' => false]);
            } catch (BulkWriteException) {
            }
        }

        return array_sum(array_column($documents, 'views'));
    }

    public function firstLogDay(): ?\DateTimeImmutable
    {
        return $this->edgeDay($this->logs(), 1);
    }

    public function lastStatsDay(): ?\DateTimeImmutable
    {
        return $this->edgeDay($this->stats(), -1);
    }

    private function edgeDay(Collection $collection, int $order): ?\DateTimeImmutable
    {
        $document = $collection->findOne(['date' => ['$ne' => null]], ['sort' => ['date' => $order], 'projection' => ['date' => 1]]);
        if (null === $document) {
            return null;
        }

        return \DateTimeImmutable::createFromInterface($document['date']->toDateTime())
            ->setTimezone(new \DateTimeZone(date_default_timezone_get()))
            ->setTime(0, 0)
        ;
    }

    private function day(\DateTimeInterface $date): UTCDateTime
    {
        return new UTCDateTime(\DateTimeImmutable::createFromInterface($date)->setTimezone(new \DateTimeZone(date_default_timezone_get()))->setTime(0, 0));
    }

    private function upsert(Collection $collection, array $filter, array $update): void
    {
        try {
            $collection->updateOne($filter, $update, ['upsert' => true]);
        } catch (BulkWriteException $exception) {
            if (self::DUPLICATE_KEY !== $exception->getWriteResult()->getWriteErrors()[0]?->getCode()) {
                throw $exception;
            }
            $collection->updateOne($filter, $update);
        }
    }

    private function totals(): Collection
    {
        return $this->documentManager->getDocumentCollection(ViewsDailyTotals::class);
    }

    private function stats(): Collection
    {
        return $this->documentManager->getDocumentCollection(ViewsDailyStats::class);
    }

    private function visitors(): Collection
    {
        return $this->documentManager->getDocumentCollection(ViewsDailyVisitor::class);
    }

    private function logs(): Collection
    {
        return $this->documentManager->getDocumentCollection(ViewsLog::class);
    }
}
