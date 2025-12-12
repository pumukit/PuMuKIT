<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Application\List;

final class ListCpusResponse
{
    public function __construct(
        public array $cpus,
        public array $cpusInMaintenance,
        public array $localCpus,
        public array $remoteCpus
    ) {}
}
