<?php

declare(strict_types=1);

namespace App\MediaProcessing\Cpu\Application\List;

use App\MediaProcessing\Cpu\Domain\Repository\CpuRepositoryInterface;
use App\MediaProcessing\Job\Domain\Repository\JobRepositoryInterface;
use Pumukit\EncoderBundle\Document\Job;

final class ListCpusService
{
    public function __construct(
        private readonly array $cpus,
        private readonly CpuRepositoryInterface $cpuRepository,
        private readonly JobRepositoryInterface $jobRepository
    ) {}

    public function __invoke(ListCpusRequest $request): ListCpusResponse
    {
        $cpusInMaintenance = array_map(
            fn ($cpu) => $cpu->getName(),
            $this->cpuRepository->findInMaintenance()
        );

        $executingJobs = $this->jobRepository->findAll(1, 1000, ['status' => Job::STATUS_EXECUTING]);
        $jobsByCpu = $this->groupJobsByCpu($executingJobs);

        $enrichedCpus = $this->enrichCpusWithJobCount($this->cpus, $jobsByCpu, $cpusInMaintenance);

        $localCpus = [];
        $remoteCpus = [];

        foreach ($enrichedCpus as $name => $cpu) {
            if ($this->isLocalCpu($cpu)) {
                $localCpus[$name] = $cpu;
            } else {
                $remoteCpus[$name] = $cpu;
            }
        }

        return new ListCpusResponse(
            cpus: $enrichedCpus,
            cpusInMaintenance: $cpusInMaintenance,
            localCpus: $localCpus,
            remoteCpus: $remoteCpus
        );
    }

    private function groupJobsByCpu(iterable $jobs): array
    {
        $jobsByCpu = [];
        foreach ($jobs as $job) {
            $cpuName = $job->getCpu();
            if ($cpuName) {
                $jobsByCpu[$cpuName] = ($jobsByCpu[$cpuName] ?? 0) + 1;
            }
        }

        return $jobsByCpu;
    }

    private function enrichCpusWithJobCount(array $cpus, array $jobsByCpu, array $cpusInMaintenance): array
    {
        $enriched = [];
        foreach ($cpus as $name => $cpu) {
            $enriched[$name] = array_merge($cpu, [
                'name' => $name,
                'current_jobs' => $jobsByCpu[$name] ?? 0,
                'in_maintenance' => in_array($name, $cpusInMaintenance),
            ]);
        }

        return $enriched;
    }

    private function isLocalCpu(array $cpu): bool
    {
        $host = $cpu['host'] ?? '';

        return in_array($host, ['localhost', '127.0.0.1', '::1']) || str_starts_with($host, '192.168.');
    }
}
