<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Cancel;

final class CancelJobRequest
{
    public function __construct(public string $id) {}
}
