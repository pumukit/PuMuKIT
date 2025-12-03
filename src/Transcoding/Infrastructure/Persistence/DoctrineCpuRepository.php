<?php

declare(strict_types=1);

namespace App\Transcoding\Infrastructure\Persistence;

use App\Transcoding\Domain\Repository\CpuRepositoryInterface;
use App\Shared\Infrastructure\Persistence\DoctrineObjectManager;
use Pumukit\EncoderBundle\Document\CpuStatus;

final class DoctrineCpuRepository implements CpuRepositoryInterface
{
    public function __construct(private DoctrineObjectManager $objectManager) {}

    public function findByName(string $name): ?CpuStatus
    {
        return $this->objectManager->getDocumentManager()
            ->getRepository(CpuStatus::class)
            ->findOneBy(['name' => $name]);
    }

    public function findInMaintenance(): array
    {
        return $this->objectManager->getDocumentManager()
            ->getRepository(CpuStatus::class)
            ->findBy(['status' => CpuStatus::STATUS_MAINTENANCE]);
    }

    public function save(CpuStatus $cpuStatus): void
    {
        $this->objectManager->getDocumentManager()->persist($cpuStatus);
        $this->objectManager->getDocumentManager()->flush();
    }

    public function delete(CpuStatus $cpuStatus): void
    {
        $this->objectManager->getDocumentManager()->remove($cpuStatus);
        $this->objectManager->getDocumentManager()->flush();
    }
}

