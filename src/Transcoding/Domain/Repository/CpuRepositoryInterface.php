<?php

declare(strict_types=1);

namespace App\Transcoding\Domain\Repository;

use Pumukit\EncoderBundle\Document\CpuStatus;

interface CpuRepositoryInterface
{
    public function findByName(string $name): ?CpuStatus;

    public function findInMaintenance(): array;

    public function save(CpuStatus $cpuStatus): void;

    public function delete(CpuStatus $cpuStatus): void;
}
