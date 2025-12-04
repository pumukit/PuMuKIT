<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Job\Find;

final class FindJobRequest
{
    public function __construct(public readonly string $id) {}
}
