<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Cpu\List;

final class ListCpusResponse
{
    public function __construct(
        public readonly array $cpus,
        public readonly array $cpusInMaintenance,
        public readonly array $localCpus,
        public readonly array $remoteCpus
    ) {}
}

