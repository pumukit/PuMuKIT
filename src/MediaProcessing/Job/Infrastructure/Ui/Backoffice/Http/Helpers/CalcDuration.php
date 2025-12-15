<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Ui\Backoffice\Http\Helpers;

final class CalcDuration
{
    public static function obtain(\DateTime $startTime, \DateTime $endTime): string
    {
        $interval = $startTime->diff($endTime);

        $totalSeconds = ($interval->days * 86400) +
            ($interval->h * 3600) +
            ($interval->i * 60) +
            $interval->s;

        if ($totalSeconds <= 0) {
            return '0:00';
        }

        $hours = floor($totalSeconds / 3600);
        $minutes = floor(($totalSeconds % 3600) / 60);
        $seconds = $totalSeconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $seconds);
        }

        return sprintf('%d:%02d', $minutes, $seconds);
    }
}
