<?php

declare(strict_types=1);

namespace App\Transcoding\Domain\Exception;

final class CpuNotFoundException extends \RuntimeException
{
    public function __construct(string $cpuName)
    {
        parent::__construct(sprintf('CPU with name "%s" not found', $cpuName));
    }
}
