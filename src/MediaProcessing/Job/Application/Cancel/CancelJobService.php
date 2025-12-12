<?php

declare(strict_types=1);

namespace App\MediaProcessing\Job\Application\Cancel;

use App\MediaProcessing\Job\Domain\Event\JobCancelledEvent;
use App\MediaProcessing\Job\Domain\Exception\JobNotFoundException;
use App\MediaProcessing\Job\Domain\Repository\JobRepositoryInterface;
use App\Shared\Domain\EventBusInterface;
use Pumukit\EncoderBundle\Document\Job;

final class CancelJobService
{
    public function __construct(
        private readonly JobRepositoryInterface $repository,
        private readonly EventBusInterface $eventBus
    ) {}

    public function __invoke(CancelJobRequest $request): CancelJobResponse
    {
        $job = $this->repository->find($request->id);

        if (!$job instanceof Job) {
            throw new JobNotFoundException($request->id);
        }

        $job->setStatus(Job::STATUS_PAUSED);

        $this->repository->save($job);

        $this->eventBus->dispatch(new JobCancelledEvent($job));

        return new CancelJobResponse($job);
    }
}
