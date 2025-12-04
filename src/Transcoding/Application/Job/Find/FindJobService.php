<?php

declare(strict_types=1);

namespace App\Transcoding\Application\Job\Find;

use App\Transcoding\Domain\Exception\JobNotFoundException;
use App\Transcoding\Domain\Repository\JobRepositoryInterface;

final class FindJobService
{
    public function __construct(private readonly JobRepositoryInterface $repository) {}

    public function __invoke(FindJobRequest $request): FindJobResponse
    {
        $job = $this->repository->find($request->id);

        if (!$job) {
            throw new JobNotFoundException($request->id);
        }

        return new FindJobResponse($job);
    }
}
