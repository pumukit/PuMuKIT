<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Find;

final class FindJobRequest
{
    public function __construct(public readonly string $id) {}
}
