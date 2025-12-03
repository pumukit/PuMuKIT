<?php

declare(strict_types=1);

namespace App\Transcoding\Infrastructure\Persistence;

use App\Transcoding\Domain\Repository\CpuRepositoryInterface;
use Doctrine\ODM\MongoDB\DocumentManager;
use Pumukit\EncoderBundle\Document\CpuStatus;

final class DoctrineCpuRepository implements CpuRepositoryInterface
{
    public function __construct(private readonly DocumentManager $documentManager) {}

    public function findByName(string $name): ?CpuStatus
    {
        return $this->documentManager
            ->getRepository(CpuStatus::class)
            ->findOneBy(['name' => $name]);
    }

    public function findInMaintenance(): array
    {
        return $this->documentManager
            ->getRepository(CpuStatus::class)
            ->findBy(['status' => CpuStatus::STATUS_MAINTENANCE]);
    }

    public function save(CpuStatus $cpuStatus): void
    {
        $this->documentManager->persist($cpuStatus);
        $this->documentManager->flush();
    }

    public function delete(CpuStatus $cpuStatus): void
    {
        $this->documentManager->remove($cpuStatus);
        $this->documentManager->flush();
    }
}

