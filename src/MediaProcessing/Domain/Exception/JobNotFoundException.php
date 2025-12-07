<?php

declare(strict_types=1);

namespace App\MediaProcessing\Domain\Exception;

final class JobNotFoundException extends \RuntimeException
{
    public function __construct(string $id)
    {
        parent::__construct(sprintf('Job with ID "%s" not found', $id));
    }
}
