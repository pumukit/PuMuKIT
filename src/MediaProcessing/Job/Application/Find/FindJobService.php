<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Find;

use Pumukit\EncoderBundle\Document\Job;
use App\MediaProcessing\Job\Domain\Exception\JobNotFoundException;
use App\MediaProcessing\Job\Domain\Repository\JobRepositoryInterface;

final class FindJobService
{
    public function __construct(private readonly JobRepositoryInterface $repository) {}

    public function __invoke(FindJobRequest $request): FindJobResponse
    {
        FindJobValidator::validate($request);

        $job = $this->repository->find($request->id);

        if (!$job instanceof Job) {
            throw new JobNotFoundException($request->id);
        }

        return new FindJobResponse($job);
    }
}
