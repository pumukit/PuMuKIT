<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Infrastructure\Security\Permission;

final class JobPermissions
{
    public const VIEW = 'job.view';
    public const STOP = 'job.stop';

    public static function all(): array
    {
        return [
            self::VIEW => 'View jobs',
            self::STOP => 'Stop jobs',
        ];
    }
}
