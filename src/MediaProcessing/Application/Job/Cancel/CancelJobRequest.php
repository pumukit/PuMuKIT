<?php

declare(strict_types=1);

namespace App\MediaProcessing\Application\Job\Cancel;

final class CancelJobRequest
{
    public function __construct(public readonly string $id) {}
}
