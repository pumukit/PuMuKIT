<?php

declare(strict_types=1);

namespace Pumukit\StatsBundle\Services;

final class StatsQuery
{
    public const INTERVALS = ['day', 'week', 'month'];

    public function __construct(
        public readonly \DateTimeImmutable $from,
        public readonly \DateTimeImmutable $to,
        public readonly ?string $seriesId = null,
        public readonly ?string $multimediaObjectId = null,
    ) {}

    public function start(): \DateTimeImmutable
    {
        return $this->from->setTime(0, 0);
    }

    public function end(): \DateTimeImmutable
    {
        return $this->to->setTime(23, 59, 59);
    }

    public function days(): int
    {
        return $this->from->diff($this->to)->days + 1;
    }

    public function previous(): self
    {
        $days = $this->days();

        return new self(
            $this->from->modify("-{$days} days"),
            $this->from->modify('-1 day'),
            $this->seriesId,
            $this->multimediaObjectId,
        );
    }

    public function interval(?string $requested = null): string
    {
        if (in_array($requested, self::INTERVALS, true)) {
            return $requested;
        }

        return match (true) {
            $this->days() <= 92 => 'day',
            $this->days() <= 366 => 'week',
            default => 'month',
        };
    }

    public function buckets(string $interval): array
    {
        $cursor = match ($interval) {
            'week' => $this->from->modify('monday this week'),
            'month' => $this->from->modify('first day of this month'),
            default => $this->from,
        };
        $step = match ($interval) {
            'week' => '+1 week',
            'month' => '+1 month',
            default => '+1 day',
        };

        $buckets = [];
        while ($cursor <= $this->to) {
            $buckets[] = $cursor->format('Y-m-d');
            $cursor = $cursor->modify($step);
        }

        return $buckets;
    }
}
